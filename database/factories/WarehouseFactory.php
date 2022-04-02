<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'material_id' => $this->faker->numberBetween(1, 4),
            'remainder' => $this->faker->numberBetween(10, 50),
            'price' => $this->faker->numberBetween(100, 500),
        ];
    }
}
