<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'price',
        'category_id',
        'nutritional_info',
    ];

    public function categories()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function portionSizes()
    {
        return $this->hasMany(PortionSize::class);
    }

    public function ingredients()
    {
        return $this->belongsToMany(Ingredient::class, 'menu_ingredients');
    }

    public function images()
    {
        return $this->hasOne(Image::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'menu_tags');
    }
}
