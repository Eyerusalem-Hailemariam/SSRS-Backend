<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id', 'shift_id', 'date', 'start_time', 'end_time', 'is_overtime', "overtime_type", "is_night_shift"
    ];
    
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
