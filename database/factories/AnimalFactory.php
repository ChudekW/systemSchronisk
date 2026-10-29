<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Animal> */
class AnimalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'registration_number' => fake()->unique()->bothify('ZW-####-????'),
            'name' => fake()->firstName(),
            'species' => 'dog',
            'sex' => 'unknown',
            'admitted_at' => today(),
            'admission_reason' => 'found',
            'status' => 'admitted',
            'sterilization_status' => 'unknown',
        ];
    }
}
