<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'email', 'password', 'role', 'total_salary', 'overtime_rate', 'tips'
    ];

    protected $hidden = [
        'password'
    ];

}
