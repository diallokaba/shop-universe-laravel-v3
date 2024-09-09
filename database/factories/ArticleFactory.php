<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'libelle' => $this->faker->unique()->word(),  // Assurez que 'libelle' est unique
            'reference' => $this->faker->unique()->word(),  // Assurez que 'reference' est unique
            'prix' => $this->faker->randomFloat(2, 10, 1000),
            'quantite' => $this->faker->numberBetween(1, 100),
        ];
    }
}
