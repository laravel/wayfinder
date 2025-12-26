<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /**
     * @return BelongsTo<Category, $this>
     */
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function relatedProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'parent_id');
    }
}
