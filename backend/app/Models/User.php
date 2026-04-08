<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable
{
 use HasFactory, HasApiTokens, Notifiable;

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'college_id',
        'position',
        'fullname',
        'email',
        'password',
        'phone_number',
        'is_active',
    ];

    /**
     * Hidden attributes (not returned in JSON)
     */

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

     // ✅ FIXED relationship
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class, 'id');
    }
}
