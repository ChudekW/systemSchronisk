<?php

namespace Database\Factories;

use App\Models\AdoptionApplication;
use App\Models\Animal;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AdoptionApplication> */
class AdoptionApplicationFactory extends Factory
{
    public function definition(): array
    {
        return ['animal_id' => Animal::factory(), 'candidate_name' => fake()->name(), 'email' => fake()->safeEmail(), 'housing' => 'Mieszkanie, zgoda domowników.', 'status' => 'new'];
    }
}
