<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'qr_code',
        'table_status',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
