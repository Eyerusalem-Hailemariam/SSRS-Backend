<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;
    protected $fillable = ['staff_id', 'total_days_worked', 'overtime_days',
    'base_pay', 'overtime_pay', 'tip_share', 'total_compensation', 'payroll_date' ];


    public function staff(){
        return $this->belongsTo(Staff::class);
    }
}
