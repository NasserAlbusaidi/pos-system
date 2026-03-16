<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class Shop extends Model
{
    // NOTE: Run `composer require laravel/cashier` before using this model.
    use Billable, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'slug',
        'branding',
        'tax_rate',
        'currency_code',
        'currency_symbol',
        'currency_decimals',
    ];

    protected $casts = [
        'branding' => 'json',
        'trial_ends_at' => 'datetime',
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

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function pricingRules()
    {
        return $this->hasMany(PricingRule::class);
    }
}
