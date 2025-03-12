<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;
    protected $fillable = ['staff_id', 'mode', 'scanned_at'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
