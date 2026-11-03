<?php

namespace Database\Factories;

use App\Models\Animal;
use App\Models\MedicalRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MedicalRecord> */
class MedicalRecordFactory extends Factory
{
    public function definition(): array
    {
        return ['animal_id' => Animal::factory(), 'author_name' => 'Lekarz testowy', 'kind' => 'visit', 'title' => 'Badanie kontrolne', 'occurred_on' => today()->toDateString(), 'performed_by' => 'Lekarz testowy', 'description' => 'Opis badania.'];
    }
}
