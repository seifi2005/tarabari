<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipmentController extends Controller
{
    /**
     * لیست سفارشات با فیلتر و pagination
     * 
     * Query Parameters:
     * - status: فیلتر بر اساس وضعیت (pending, processing, completed, cancelled)
     * - receptor: فیلتر بر اساس نام یا ID پذیرنده
     * - sort: مرتب‌سازی (created_at, -created_at, status, -status)
     * - page: شماره صفحه
     * - per_page: تعداد آیتم در هر صفحه (پیش‌فرض: 15)
     */
    public function index(Request $request)
    {
        $request->validate([
            'status' => 'nullable|string|in:pending,processing,completed,cancelled',
            'receptor' => 'nullable|string', // می‌تواند ID یا نام شرکت باشد
            'sort' => 'nullable|string|in:created_at,-created_at,status,-status,system_order_id,-system_order_id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        // استفاده از eager loading برای رابطه receptor
        // توجه: چون Shipment در orders_db و Receptor در core_db است، باید به صورت دستی لود کنیم
        $query = Shipment::query();

        // فیلتر بر اساس وضعیت
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // فیلتر بر اساس پذیرنده
        if ($request->has('receptor') && $request->receptor) {
            $receptorValue = $request->receptor;
            
            // اگر عدد است، به عنوان ID در نظر بگیر
            if (is_numeric($receptorValue)) {
                $query->where('receptor_id', $receptorValue);
            } else {
                // در غیر این صورت، بر اساس نام شرکت جستجو کن
                // چون cross-database است، باید به صورت دستی جستجو کنیم
                $receptorIds = \App\Models\Receptor::where('company_name', 'like', "%{$receptorValue}%")
                    ->orWhere('first_name', 'like', "%{$receptorValue}%")
                    ->orWhere('last_name', 'like', "%{$receptorValue}%")
                    ->pluck('id')
                    ->toArray();
                
                if (!empty($receptorIds)) {
                    $query->whereIn('receptor_id', $receptorIds);
                } else {
                    // اگر هیچ پذیرنده‌ای پیدا نشد، نتیجه خالی برگردان
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // مرتب‌سازی
        $sort = $request->get('sort', '-created_at'); // پیش‌فرض: جدیدترین اول
        if ($sort) {
            if (str_starts_with($sort, '-')) {
                $column = substr($sort, 1);
                $query->orderBy($column, 'desc');
            } else {
                $query->orderBy($sort, 'asc');
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $shipments = $query->paginate($perPage);

        // لود کردن Receptor ها به صورت دستی (چون cross-database است)
        $receptorIds = collect($shipments->items())->pluck('receptor_id')->filter()->unique()->toArray();
        $receptors = \App\Models\Receptor::whereIn('id', $receptorIds)
            ->get()
            ->keyBy('id');

        // فرمت کردن پاسخ
        $formattedShipments = collect($shipments->items())->map(function ($shipment) use ($receptors) {
            $receptor = $shipment->receptor_id ? ($receptors->get($shipment->receptor_id) ?? null) : null;
            
            return [
                'id' => $shipment->id,
                'system_order_id' => $shipment->system_order_id,
                'source_order_id' => $shipment->source_order_id,
                'customer' => [
                    'first_name' => $shipment->customer_first_name,
                    'last_name' => $shipment->customer_last_name,
                    'full_name' => $shipment->customer_full_name,
                ],
                'origin' => $shipment->origin,
                'destination' => [
                    'city' => $shipment->destination_city,
                    'address' => $shipment->address,
                    'postcode' => $shipment->postcode,
                ],
                'receptor' => $receptor ? [
                    'id' => $receptor->id,
                    'company_name' => $receptor->company_name,
                    'name' => $receptor->first_name . ' ' . $receptor->last_name,
                ] : null,
                'total_price' => (float) $shipment->total_price,
                'status' => $shipment->status,
                'created_at' => $shipment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $shipment->updated_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedShipments,
            'meta' => [
                'current_page' => $shipments->currentPage(),
                'per_page' => $shipments->perPage(),
                'total' => $shipments->total(),
                'last_page' => $shipments->lastPage(),
                'from' => $shipments->firstItem(),
                'to' => $shipments->lastItem(),
            ],
            'filters' => [
                'status' => $request->get('status'),
                'receptor' => $request->get('receptor'),
                'sort' => $request->get('sort', '-created_at'),
            ],
        ]);
    }

    /**
     * نمایش جزئیات یک سفارش
     */
    public function show($id)
    {
        $shipment = Shipment::with('orderItems.pricing')->findOrFail($id);
        
        // لود کردن Receptor به صورت دستی (چون cross-database است)
        $receptor = $shipment->receptor_id 
            ? \App\Models\Receptor::find($shipment->receptor_id)
            : null;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $shipment->id,
                'system_order_id' => $shipment->system_order_id,
                'source_order_id' => $shipment->source_order_id,
                'customer' => [
                    'first_name' => $shipment->customer_first_name,
                    'last_name' => $shipment->customer_last_name,
                    'full_name' => $shipment->customer_full_name,
                    'mobile' => $shipment->mobile,
                ],
                'origin' => $shipment->origin,
                'destination' => [
                    'city' => $shipment->destination_city,
                    'address' => $shipment->address,
                    'postcode' => $shipment->postcode,
                ],
                'receptor' => $receptor ? [
                    'id' => $receptor->id,
                    'company_name' => $receptor->company_name,
                    'name' => $receptor->first_name . ' ' . $receptor->last_name,
                ] : null,
                'total_price' => (float) $shipment->total_price,
                'status' => $shipment->status,
                'items' => $shipment->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'product_id' => $item->product_id,
                        'variation_id' => $item->variation_id,
                        'quantity' => $item->quantity,
                        'sku' => $item->sku,
                        'pricing' => $item->pricing ? [
                            'item_name' => $item->pricing->item_name,
                            'unit_price' => (float) $item->pricing->unit_price,
                            'subtotal' => (float) $item->pricing->subtotal,
                            'total' => (float) $item->pricing->total,
                        ] : null,
                    ];
                }),
                'created_at' => $shipment->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $shipment->updated_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }
}

