<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $createdAt = $this->faker->dateTimeThisDecade();

        return [
            'name' => $this->faker->unique()->word(),
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];
    }
}
