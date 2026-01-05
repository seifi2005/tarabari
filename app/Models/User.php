<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $connection = 'core_db';

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'mobile',
        'national_code',
        'username',
        'password',
        'role',
        'receptor_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // JWT Methods (for receptor authentication)
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
            'receptor_id' => $this->receptor_id,
        ];
    }

    // Relationships
    public function receptor()
    {
        return $this->belongsTo(Receptor::class);
    }

    // Helper methods
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isOperator(): bool
    {
        return $this->role === 'operator';
    }

    public function isReceptor(): bool
    {
        return $this->role === 'receptor';
    }

    public function canAccess($resource): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->isOperator()) {
            return in_array($resource, ['users', 'receptors']);
        }

        if ($this->isReceptor()) {
            return $resource === 'own_data';
        }

        return false;
    }
}
