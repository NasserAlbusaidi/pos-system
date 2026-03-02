<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'shop_id' => \App\Models\Shop::factory(), // Usually overridden
            'category_id' => \App\Models\Category::factory(), // Usually overridden
            'name' => fake()->word(),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 1, 10),
            'is_available' => true,
        ];
    }
}
