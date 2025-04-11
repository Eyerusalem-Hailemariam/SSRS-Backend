<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"name", "email", "password", "role", "is_verified"},
 *     @OA\Property(property="id", type="integer", description="The unique identifier of the user", example=1),
 *     @OA\Property(property="name", type="string", description="The name of the user", example="Admin User"),
 *     @OA\Property(property="email", type="string", description="The email of the user", format="email", example="admin@example.com"),
 *     @OA\Property(property="password", type="string", description="The password of the user", example="securePassword123"),
 *     @OA\Property(property="role", type="string", description="The role of the user", example="admin"),
 *     @OA\Property(property="otp", type="string", description="One-time password for user verification", example="123456"),
 *     @OA\Property(property="is_verified", type="boolean", description="User's verification status", example=false),
 *     @OA\Property(property="email_verified_at", type="string", description="Timestamp for when the email was verified", format="date-time", example="2025-04-10T14:12:34Z")
 * )
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'otp',
        'is_verified',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'otp',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_verified' => 'boolean',
    ];
}
