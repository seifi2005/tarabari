<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Receptor extends Model
{
    use HasFactory;

    protected $connection = 'core_db';

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'mobile',
        'allowed_ip',
        'username',
        'password',
        'orders_base_url',
        'orders_auth_token',
        'callback_url',
    ];

    protected $hidden = [
        'password',
        'orders_auth_token',
    ];

    // Password is hashed in controller, no need for cast

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function workflow()
    {
        return $this->hasOne(ReceptorWorkflow::class);
    }

    public function providers()
    {
        return $this->belongsToMany(Provider::class, 'receptor_provider', 'receptor_id', 'provider_id')
            ->withTimestamps();
    }

    // Events
    protected static function boot()
    {
        parent::boot();

        static::created(function ($receptor) {
            // ایجاد کاربر خودکار برای receptor
            $user = User::create([
                'name' => $receptor->first_name,
                'last_name' => $receptor->last_name,
                'email' => $receptor->username . '@receptor.local',
                'mobile' => $receptor->mobile,
                'username' => $receptor->username,
                'password' => $receptor->password,
                'role' => 'receptor',
                'receptor_id' => $receptor->id,
            ]);
        });

        static::updated(function ($receptor) {
            // آپدیت کاربر مرتبط
            if ($receptor->user) {
                $receptor->user->update([
                    'name' => $receptor->first_name,
                    'last_name' => $receptor->last_name,
                    'mobile' => $receptor->mobile,
                    'username' => $receptor->username,
                    'password' => $receptor->password,
                ]);
            }
        });

        static::deleting(function ($receptor) {
            // حذف کاربر مرتبط
            if ($receptor->user) {
                $receptor->user->delete();
            }
        });
    }

    /**
     * بررسی اینکه آیا پذیرنده تنظیمات API سفارشات را دارد
     */
    public function hasOrdersApiConfigured(): bool
    {
        return !empty($this->orders_base_url) && !empty($this->orders_auth_token);
    }
}

