<?php

namespace App\Jobs;

use App\Jobs\ExecuteReceptorWorkflowJob;
use App\Models\OrderItem;
use App\Models\OrderItemPricing;
use App\Models\Receptor;
use App\Models\Shipment;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $receptorId;
    public int $orderId;
    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(int $receptorId, int $orderId)
    {
        $this->receptorId = $receptorId;
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        try {
            $receptor = Receptor::findOrFail($this->receptorId);
            
            if (!$receptor->hasOrdersApiConfigured()) {
                Log::warning('Receptor API not configured', [
                    'receptor_id' => $this->receptorId,
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            $orderService = new OrderService($receptor);

            // دریافت جزئیات سفارش
            $orderData = $orderService->fetchOrderDetails($this->orderId);

            if (!$orderData) {
                Log::warning('Order details not found', [
                    'receptor_id' => $this->receptorId,
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // بررسی اینکه آیا این سفارش قبلاً ثبت شده است
            $existingShipment = Shipment::where('receptor_id', $this->receptorId)
                ->where('source_order_id', (string) $orderData['id'])
                ->first();
            
            if ($existingShipment) {
                Log::info('Order already exists', [
                    'receptor_id' => $this->receptorId,
                    'order_id' => $this->orderId,
                    'shipment_id' => $existingShipment->id,
                ]);
                return;
            }

            // استخراج اطلاعات از پاسخ API
            $billing = $orderData['billing'] ?? [];
            $shipping = $orderData['shipping'] ?? [];
            $lineItems = $orderData['line_items'] ?? [];

            // ایجاد محموله
            $shipment = Shipment::create([
                'receptor_id' => $this->receptorId,
                'source_order_id' => (string) $orderData['id'],
                'customer_first_name' => $billing['first_name'] ?? '',
                'customer_last_name' => $billing['last_name'] ?? '',
                'origin' => 'تهران',
                'destination_city' => $shipping['city'] ?? $billing['city'] ?? '',
                'address' => $shipping['address_1'] ?? $billing['address_1'] ?? '',
                'postcode' => $shipping['postcode'] ?? $billing['postcode'] ?? '',
                'mobile' => $billing['phone'] ?? '',
                'total_price' => (float) ($orderData['total'] ?? 0),
                'status' => 'processing',
            ]);

            // ایجاد آیتم‌های سفارش
            foreach ($lineItems as $item) {
                // ایجاد آیتم (اطلاعات محصول)
                $orderItem = OrderItem::create([
                    'shipment_id' => $shipment->id,
                    'source_item_id' => (string) ($item['id'] ?? ''),
                    'product_id' => (int) ($item['product_id'] ?? 0),
                    'variation_id' => (int) ($item['variation_id'] ?? 0),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'sku' => $item['sku'] ?? null,
                ]);

                // ایجاد قیمت‌گذاری (اطلاعات مالی)
                OrderItemPricing::create([
                    'order_item_id' => $orderItem->id,
                    'item_name' => $item['name'] ?? '',
                    'unit_price' => (float) ($item['price'] ?? 0),
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'subtotal' => (float) ($item['subtotal'] ?? 0),
                    'discount' => 0, // اگر API تخفیف داشت اینجا اضافه شود
                    'tax' => 0, // اگر API مالیات داشت اینجا اضافه شود
                    'total' => (float) ($item['total'] ?? 0),
                    'currency' => 'IRR',
                ]);
            }

            Log::info('Order processed successfully', [
                'receptor_id' => $this->receptorId,
                'order_id' => $this->orderId,
                'shipment_id' => $shipment->id,
                'system_order_id' => $shipment->system_order_id,
            ]);

            // اجرای Workflow (اگر فعال باشد)
            // توجه: Callback و SMS از طریق Workflow ارسال می‌شوند
            try {
                if ($receptor->workflow && $receptor->workflow->is_active) {
                    ExecuteReceptorWorkflowJob::dispatch($receptor->id, $shipment->id);
                }
            } catch (\Exception $workflowException) {
                // خطا در Workflow نباید باعث fail شدن job شود
                Log::warning('Workflow dispatch failed but order was saved', [
                    'receptor_id' => $this->receptorId,
                    'order_id' => $this->orderId,
                    'shipment_id' => $shipment->id,
                    'error' => $workflowException->getMessage(),
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Error processing order', [
                'receptor_id' => $this->receptorId,
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * تبدیل وضعیت سفارش از سیستم مبدا به وضعیت داخلی
     */
    private function mapOrderStatus(string $status): string
    {
        $statusMap = [        
            'processing-order' => 'پردازش ارسال',
            'on-hold' => 'pending',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
            'refunded' => 'cancelled',
            'failed' => 'cancelled',
        ];

        return $statusMap[strtolower($status)] ?? 'pending';
    }
}
