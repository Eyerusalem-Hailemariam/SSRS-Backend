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
        'order_id',
    ];

    public function order()
{
    return $this->hasOne(Order::class, 'tx_ref', 'tx_ref');
}
}
