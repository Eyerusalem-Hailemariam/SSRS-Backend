<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class image extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_name',
        'file_path',
        'file_type',
        'menu_item_id'
    ];
    public function menuItem()
    {
        return $this->belongsTo(MenuItem::class);
    }
}
