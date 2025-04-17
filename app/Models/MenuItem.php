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
        'total_calorie'
       
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ingredients()
{
    return $this->belongsToMany(Ingredient::class, 'menu_ingredients')
                ->withPivot('quantity')
                ->withTimestamps(); // optional
}

    public function menuIngredients()
    {
        return $this->hasMany(MenuIngredient::class, 'menu_item_id');
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
