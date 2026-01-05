<?php

namespace App\Services;

use App\Models\Receptor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderService
{
    private Receptor $receptor;
    private string $baseUrl;
    private string $authToken;

    public function __construct(Receptor $receptor)
    {
        $this->receptor = $receptor;
        $this->baseUrl = rtrim($receptor->orders_base_url ?? '', '/');
        $this->authToken = $receptor->orders_auth_token ?? '';
    }

    /**
     * بررسی اینکه آیا پذیرنده تنظیمات API را دارد
     */
    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->authToken);
    }

    /**
     * دریافت لیست ID سفارش‌ها
     * آدرس: {base_url}/orders/
     */
    public function fetchOrderIds(): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Order API is not configured for this receptor');
        }

        try {
//            $url = $this->baseUrl . '/orders/';
            $url = 'https://hamta-tarabar.com/orders.json';
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch order IDs', [
                    'receptor_id' => $this->receptor->id,
                    'receptor_name' => $this->receptor->company_name,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Failed to fetch order IDs: HTTP ' . $response->status());
            }

            $data = $response->json();
            

            // بررسی ساختار پاسخ: {"success": true, "data": {"orders": [{"id": 100924}, ...]}}
            if (isset($data['success']) && $data['success'] === true && isset($data['data']['orders'])) {
                $orderIds = [];
                foreach ($data['data']['orders'] as $order) {
                    if (isset($order['id'])) {
                        $orderIds[] = (int) $order['id'];
                    }
                }
                return $orderIds;
            }

            // اگر ساختار متفاوت است
            if (isset($data['orders']) && is_array($data['orders'])) {
                $orderIds = [];
                foreach ($data['orders'] as $order) {
                    if (isset($order['id'])) {
                        $orderIds[] = (int) $order['id'];
                    }
                }
                return $orderIds;
            }

            // اگر مستقیماً آرایه از ID ها است
            if (is_array($data) && !empty($data) && isset($data[0]) && is_numeric($data[0])) {
                return array_map('intval', $data);
            }

            Log::warning('Unexpected response structure from orders API', [
                'receptor_id' => $this->receptor->id,
                'data' => $data,
            ]);
            return [];

        } catch (\Exception $e) {
            Log::error('Error fetching order IDs', [
                'receptor_id' => $this->receptor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * دریافت جزئیات یک سفارش
     * آدرس: {base_url}/orders/{id}
     */
    public function fetchOrderDetails(int $orderId): ?array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Order API is not configured for this receptor');
        }

        try {
            $url = $this->baseUrl . '/orders/' . $orderId;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
            ])->timeout(30)->get($url);

            if ($response->failed()) {
                Log::error('Failed to fetch order details', [
                    'receptor_id' => $this->receptor->id,
                    'order_id' => $orderId,
                    'url' => $url,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            // پشتیبانی از ساختارهای مختلف پاسخ
            if (isset($data['success']) && $data['success'] === true && isset($data['data']['result'])) {
                return $data['data']['result'];
            }

            // اگر result مستقیماً در data باشد
            if (isset($data['data']) && is_array($data['data']) && !isset($data['data']['result'])) {
                return $data['data'];
            }

            // اگر result مستقیماً در root باشد
            if (isset($data['result']) && is_array($data['result'])) {
                return $data['result'];
            }

            // اگر خود data یک object سفارش است
            if (isset($data['id']) && isset($data['status'])) {
                return $data;
            }

            Log::warning('Unexpected response structure from order details API', [
                'receptor_id' => $this->receptor->id,
                'order_id' => $orderId,
                'data' => $data,
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Error fetching order details', [
                'receptor_id' => $this->receptor->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * ارسال callback به مبدا پس از ثبت موفق سفارش
     * 
     * @param int $sourceOrderId شناسه سفارش در سیستم مبدا
     * @param string $systemOrderId شناسه سفارش در سامانه ترابری
     * @param int $shipmentId شناسه محموله
     * @param string|null $note یادداشت اختیاری
     * @return bool موفقیت ارسال
     */
    public function sendCallback(int $sourceOrderId, string $systemOrderId, int $shipmentId, ?string $note = null): bool
    {
        // اگر orders_base_url تنظیم نشده باشد
        if (empty($this->baseUrl)) {
            Log::info('Orders base URL not configured', [
                'receptor_id' => $this->receptor->id,
                'order_id' => $sourceOrderId,
            ]);
            return false;
        }

        try {
            // ساخت URL: orders_base_url + /orders/{id}/status
            $callbackUrl = rtrim($this->baseUrl, '/') . '/orders/' . $sourceOrderId . '/status';
            
            // ساخت payload
            // توجه: وضعیت "tarabar-process" برای اطلاع به پذیرنده
            $payload = [
                'status' => 'tarabar-proc',
                'note' => $note ?? 'سفارش در سامانه ترابری ثبت شد',
            ];

            // ارسال callback با متد PUT
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->authToken,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->put($callbackUrl, $payload);

            if ($response->successful()) {
                Log::info('Callback sent successfully', [
                    'receptor_id' => $this->receptor->id,
                    'source_order_id' => $sourceOrderId,
                    'system_order_id' => $systemOrderId,
                    'shipment_id' => $shipmentId,
                    'callback_url' => $callbackUrl,
                    'response_status' => $response->status(),
                ]);
                return true;
            } else {
                $body = json_decode($response->body(), true);
                Log::warning('Callback failed', [
                    'receptor_id' => $this->receptor->id,
                    'source_order_id' => $sourceOrderId,
                    'callback_url' => $callbackUrl,
                    'response_status' => $response->status(),                    
                    'response_body' => $body, 
                ]);
                return false;
            }

        } catch (\Exception $e) {
            Log::error('Error sending callback', [
                'receptor_id' => $this->receptor->id,
                'source_order_id' => $sourceOrderId,
                'callback_url' => $callbackUrl ?? 'not set',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
