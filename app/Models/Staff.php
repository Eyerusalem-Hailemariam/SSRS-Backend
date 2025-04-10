<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use OpenApi\Annotations as OA;

/**
 * @OA\Schema(
 *     schema="Staff",
 *     type="object",
 *     required={"name", "staff_id", "email", "password", "role", "total_salary", "overtime_rate", "tips"},
 *     @OA\Property(property="id", type="integer", description="The unique identifier of the staff", example=1),
 *     @OA\Property(property="name", type="string", description="The name of the staff member", example="John Doe"),
 *     @OA\Property(property="staff_id", type="string", description="The unique staff ID", example="STAFF-12345"),
 *     @OA\Property(property="email", type="string", description="The email of the staff member", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="password", type="string", description="The password of the staff member", example="securePassword123"),
 *     @OA\Property(property="role", type="string", description="The role of the staff member", example="manager"),
 *     @OA\Property(property="total_salary", type="number", format="float", description="The total salary of the staff member", example=5000.00),
 *     @OA\Property(property="overtime_rate", type="number", format="float", description="The overtime rate per hour", example=20.00),
 *     @OA\Property(property="tips", type="number", format="float", description="The tips earned by the staff member", example=150.00)
 * )
 */
class Staff extends Authenticatable implements CanResetPassword
{
    use Notifiable, HasFactory, HasApiTokens, CanResetPasswordTrait;

    protected $fillable = [
        'name', 'staff_id', 'email', 'password', 'role', 'total_salary', 'overtime_rate', 'tips'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($staff) {
            $staff->staff_id = $staff->staff_id ?? 'STAFF-'.strtoupper(uniqid());
        });
    }

    protected $hidden = [
        'password'
    ];
}
