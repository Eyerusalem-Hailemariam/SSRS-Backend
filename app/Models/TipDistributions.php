<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipDistributions extends Model
{
    use HasFactory;

    protected $fillable = ['staff_id', 'payment_id', 'amount'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
