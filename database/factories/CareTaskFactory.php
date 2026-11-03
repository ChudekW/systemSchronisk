<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\CareTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CareTask> */
class CareTaskFactory extends Factory
{
    public function definition(): array
    {
        return ['animal_id' => Animal::factory(), 'title' => 'Spacer i obserwacja', 'description' => null, 'due_date' => today()->toDateString(), 'priority' => 'normal', 'status' => 'todo'];
    }
}
