<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', // For registered customers
        'temp_id',     // For unregistered customers
        'message',     // Feedback message
        'created_at',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class);
    }
}
