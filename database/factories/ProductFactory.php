<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $purchasePrice = fake()->numberBetween(5000, 50000);

        return [
            'category_id' => Category::factory(),
            'sku' => 'BRG-'.fake()->unique()->numerify('#####'),
            'name' => fake()->words(3, true),
            'unit' => 'pcs',
            'purchase_price' => $purchasePrice,
            'selling_price' => $purchasePrice + fake()->numberBetween(2000, 20000),
            'stock' => fake()->numberBetween(10, 100),
            'minimum_stock' => 5,
            'is_active' => true,
        ];
    }
}
