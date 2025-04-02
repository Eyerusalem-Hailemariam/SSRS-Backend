<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuTag extends Model
{
    use HasFactory;
    protected $fillable = [
        'menu_item_id',
        'tag_id',
    ];

    public function Tag()
    {
        return $this->hasMany(Tag::class);
    }
    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
