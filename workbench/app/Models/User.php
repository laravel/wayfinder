<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $with = ['ownedProducts', 'favoriteCategories'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * @return Attribute<string>
     */
    protected function formattedName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'User: '.$this->name,
        );
    }

    protected function withoutDoc(): Attribute
    {
        return Attribute::make(
            get: fn (): string => 'without doc',
        );
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function ownedProducts(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function favoriteCategories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
