<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $table = 'payroll';
   protected $fillable = [
    'staff_id',
    'start_date',
    'end_date',
    'total_salary',
    'assigned_days',
    'total_earned',
    'tax',
    'tips',
    'net_salary_without_tips',
    'net_salary_with_tips',
];


    public function staff(){
        return $this->belongsTo(Staff::class);
    }
}
