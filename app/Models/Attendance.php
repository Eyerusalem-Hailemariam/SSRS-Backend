<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = [
        'staff_id',
        'mode',
        'status',
        'late_minutes',
        'early_minutes',
        'shift_id',
        'scanned_at',
        'staff_shift_id',
    ];


    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
    protected $table = 'attendance';
    public $timestamps = false;

}
