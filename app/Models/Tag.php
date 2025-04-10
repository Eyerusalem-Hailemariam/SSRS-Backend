<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
    ];


    public function menuItem()
{
    return $this->belongsToMany(MenuItem::class, 'menu_tag', 'tag_id', 'menu_item_id');
}

}
