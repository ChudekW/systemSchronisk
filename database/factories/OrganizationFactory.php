<?php

namespace Database\Factories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Organization> */
class OrganizationFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->company(), 'shelter_name' => 'Schronisko '.fake()->city(), 'is_active' => true];
    }
}
