<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tx_ref',
        'amount',
        'currency',
        'status',
        'email',
        'first_name',
        'last_name',
        'phone_number',
    ];
}
