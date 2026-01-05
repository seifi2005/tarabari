<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Provider extends Model
{
    use HasFactory;

    protected $connection = 'core_db';

    protected $fillable = [
        'name',
        'code',
        'api_base_url',
        'api_username',
        'api_password',
        'api_key',
        'is_active',
        'config',
    ];

    protected $hidden = [
        'api_password',
        'api_key',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    /**
     * رمزنگاری password هنگام ذخیره
     */
    public function setApiPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['api_password'] = Crypt::encryptString($value);
        }
    }

    /**
     * رمزگشایی password هنگام خواندن
     */
    public function getApiPasswordAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Relationship: Provider belongs to many Receptors
     */
    public function receptors()
    {
        return $this->belongsToMany(Receptor::class, 'receptor_provider', 'provider_id', 'receptor_id')
            ->withTimestamps();
    }

    /**
     * Relationship: Provider has many Shipments
     */
    public function shipments()
    {
        return $this->hasMany(Shipment::class, 'provider_id', 'id');
    }

    /**
     * بررسی اینکه provider تنظیمات API را دارد
     */
    public function isConfigured(): bool
    {
        return !empty($this->api_base_url) && 
               !empty($this->api_username) && 
               !empty($this->api_password);
    }
}

