<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;

class Staff extends Authenticatable implements CanResetPassword
{
    use Notifiable, HasFactory, HasApiTokens, CanResetPasswordTrait;

    protected $fillable = [
        'name', 'staff_id', 'email', 'password', 'role', 'total_salary', 'overtime_rate', 'tips'
    ];

    protected static function boot(){
        parent::boot();

        static::creating(function ($staff) {
            $staff->staff_id = $staff->staff_id ?? 'STAFF-'.strtoupper(uniqid());
        });
    }

    protected $hidden = [
        'password'
    ];
}
