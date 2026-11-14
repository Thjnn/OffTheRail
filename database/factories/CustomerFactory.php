<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phone' => '09'.fake()->unique()->numerify('########'),
            'name' => fake()->name(),
            'password' => null,
        ];
    }

    public function registered(): static
    {
        return $this->state(['password' => 'matkhau123', 'registered_at' => now()]);
    }
}
