<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'customer_id',
        'table_id',
        'order_date_time',
        'order_type',
        'total_price',
        'order_status',
        'arrived',
        'customer_ip',
        'customer_temp_id',
        'notified_arrival',
        'is_remote',
        'payment_status',
        'tx_ref' 
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
{
    return $this->belongsTo(Payment::class, 'tx_ref', 'tx_ref');
}
}
