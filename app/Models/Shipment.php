<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Shipment extends Model
{
    use HasFactory;

    protected $connection = 'orders_db';

    protected $fillable = [
        'receptor_id',
        'system_order_id',
        'source_order_id',
        'customer_first_name',
        'customer_last_name',
        'origin',
        'destination_city',
        'address',
        'postcode',
        'mobile',
        'total_price',
        'status',
        'provider_id',
        'provider_tracking_number',
        'provider_order_id',
        'sent_to_provider_at',
        'provider_response',
    ];

    protected $casts = [
        'provider_response' => 'array',
        'sent_to_provider_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($shipment) {
            if (empty($shipment->system_order_id)) {
                $shipment->system_order_id = 'ORD-' . strtoupper(Str::random(10));
            }
        });
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function receptor()
    {
        return $this->belongsTo(Receptor::class, 'receptor_id', 'id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id', 'id');
    }

    public function getCustomerFullNameAttribute()
    {
        return $this->customer_first_name . ' ' . $this->customer_last_name;
    }
}
