<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable; 


class Staff extends Model
{
    use Notifiable, HasFactory, HasApiTokens;
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
