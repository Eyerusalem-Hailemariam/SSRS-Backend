<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ingredient extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'calorie',
        ];

        public function menuItems()
        {
            return $this->belongsToMany(MenuItem::class, 'menu_ingredients')
                        ->withPivot('quantity')
                        ->withTimestamps(); // optional
        }
    
        // One-to-many relationship to the pivot model
        public function menuIngredients()
        {
            return $this->hasMany(MenuIngredient::class);
        }

}
