<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItemPricing extends Model
{
    use HasFactory;

    protected $connection = 'orders_db';
    protected $table = 'order_item_pricing';

    protected $fillable = [
        'order_item_id',
        'item_name',
        'unit_price',
        'quantity',
        'subtotal',
        'discount',
        'tax',
        'total',
        'currency',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity' => 'integer',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    // Auto-calculate total before saving
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($pricing) {
            // محاسبه خودکار total اگر خالی باشد
            if (empty($pricing->total)) {
                $pricing->total = ($pricing->subtotal + $pricing->tax) - $pricing->discount;
            }
        });
    }
}

