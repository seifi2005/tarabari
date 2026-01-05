<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessOrderJob;
use App\Models\OrderItem;
use App\Models\Receptor;
use App\Models\Shipment;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isSuperAdmin() && !auth()->user()->isOperator()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            return $next($request);
        });
    }

    /**
     * لیست پذیرنده‌ها برای دریافت سفارشات
     */
    public function getReceptorsForOrders()
    {
        $receptors = Receptor::select('id', 'company_name', 'first_name', 'last_name', 'orders_base_url')
            ->whereNotNull('orders_base_url')
            ->whereNotNull('orders_auth_token')
            ->get()
            ->map(function ($receptor) {
                return [
                    'id' => $receptor->id,
                    'company_name' => $receptor->company_name,
                    'full_name' => $receptor->first_name . ' ' . $receptor->last_name,
                    'orders_base_url' => $receptor->orders_base_url,
                    'has_api_configured' => $receptor->hasOrdersApiConfigured(),
                ];
            });

        return response()->json([
            'receptors' => $receptors,
        ]);
    }

    /**
     * بررسی وضعیت سفارش‌های موجود برای یک پذیرنده خاص
     */
    public function checkOrders(Request $request, $receptorId)
    {
        try {
            $receptor = Receptor::findOrFail($receptorId);

            if (!$receptor->hasOrdersApiConfigured()) {
                return response()->json([
                    'message' => trans('messages.order_api_not_configured'),
                    'receptor_id' => $receptorId,
                    'receptor_name' => $receptor->company_name,
                ], 400);
            }

            $orderService = new OrderService($receptor);

            // دریافت لیست ID سفارش‌ها
            $orderIds = $orderService->fetchOrderIds();

            if (empty($orderIds)) {
                return response()->json([
                    'message' => trans('messages.no_orders_found'),
                    'receptor_id' => $receptorId,
                    'receptor_name' => $receptor->company_name,
                    'queued' => 0,
                ], 200);
            }

            // اضافه کردن هر سفارش به صف پردازش
            $queuedCount = 0;
            foreach ($orderIds as $orderId) {
                if (is_numeric($orderId)) {
                    ProcessOrderJob::dispatch($receptorId, (int) $orderId);
                    $queuedCount++;
                }
            }

            return response()->json([
                'message' => 'Orders queued for processing',
                'receptor_id' => $receptorId,
                'receptor_name' => $receptor->company_name,
                'total_orders' => count($orderIds),
                'queued' => $queuedCount,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error checking orders', [
                'receptor_id' => $receptorId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => trans('messages.error_checking_orders'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * لیست محموله‌ها
     */
    public function index(Request $request)
    {
        $query = Shipment::with(['orderItems.pricing', 'receptor']);

        // فیلتر بر اساس پذیرنده
        if ($request->has('receptor_id')) {
            $query->where('receptor_id', $request->receptor_id);
        }

        // جستجو بر اساس system_order_id
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('system_order_id', 'like', "%{$search}%")
                  ->orWhere('source_order_id', 'like', "%{$search}%")
                  ->orWhere('mobile', 'like', "%{$search}%")
                  ->orWhere('customer_first_name', 'like', "%{$search}%")
                  ->orWhere('customer_last_name', 'like', "%{$search}%");
            });
        }

        // فیلتر بر اساس وضعیت
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($shipments);
    }

    /**
     * نمایش جزئیات یک محموله
     */
    public function show($id)
    {
        $shipment = Shipment::with(['orderItems.pricing', 'receptor'])->findOrFail($id);

        return response()->json($shipment);
    }

    /**
     * جستجو بر اساس system_order_id
     */
    public function search(Request $request)
    {
        $request->validate([
            'system_order_id' => 'required|string',
        ]);

        $shipment = Shipment::with(['orderItems.pricing', 'receptor'])
            ->where('system_order_id', $request->system_order_id)
            ->first();

        if (!$shipment) {
            return response()->json([
                'message' => trans('messages.shipment_not_found'),
            ], 404);
        }

        return response()->json($shipment);
    }
}
