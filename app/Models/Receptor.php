<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Receptor extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'mobile',
        'allowed_ip',
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // Password is hashed in controller, no need for cast

    // Relationships
    public function user()
    {
        return $this->hasOne(User::class);
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
}

