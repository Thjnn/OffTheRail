<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerAddressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'name' => fake()->name(),
            'phone' => '09'.fake()->numerify('########'),
            'province_code' => '01',
            'ward_code' => '00004',
            'street' => fake()->streetAddress(),
            'is_default' => false,
        ];
    }
}
