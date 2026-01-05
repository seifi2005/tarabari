<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $connection = 'orders_db';

    protected $fillable = [
        'shipment_id',
        'source_item_id',
        'product_id',
        'variation_id',
        'quantity',
        'sku',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'product_id' => 'integer',
        'variation_id' => 'integer',
    ];

    // Relationships
    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function pricing()
    {
        return $this->hasOne(OrderItemPricing::class);
    }

    // Helper attributes
    public function getTotalPriceAttribute()
    {
        return $this->pricing?->total ?? 0;
    }

    public function getNameAttribute()
    {
        return $this->pricing?->item_name ?? '';
    }

    public function getUnitPriceAttribute()
    {
        return $this->pricing?->unit_price ?? 0;
    }
}
