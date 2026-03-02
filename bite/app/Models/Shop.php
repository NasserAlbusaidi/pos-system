<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'branding',
        'tax_rate',
        'currency_code',
        'currency_symbol',
        'currency_decimals',
    ];

    protected $casts = [
        'branding' => 'json',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function modifierGroups()
    {
        return $this->hasMany(ModifierGroup::class);
    }

    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
