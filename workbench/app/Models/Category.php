<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    public static $snakeAttributes = false;

    /**
     * @return HasMany<Product, $this>
     */
    public function categoryProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function subCategories(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }
}
