<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('animals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('registration_number', 80);
            $table->string('name', 120);
            $table->string('species', 30);
            $table->string('breed', 120)->nullable();
            $table->string('sex', 20);
            $table->date('birth_date')->nullable();
            $table->boolean('birth_date_estimated')->default(false);
            $table->string('color', 120)->nullable();
            $table->decimal('weight_kg', 7, 2)->nullable();
            $table->string('microchip', 30)->nullable();
            $table->date('admitted_at');
            $table->string('admission_reason', 40);
            $table->string('found_location')->nullable();
            $table->string('location', 120)->nullable();
            $table->string('status', 40);
            $table->string('sterilization_status', 20)->default('unknown');
            $table->text('health_notes')->nullable();
            $table->text('behavior_notes')->nullable();
            $table->text('care_notes')->nullable();
            $table->text('description')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'registration_number']);
            $table->unique(['organization_id', 'microchip']);
            $table->index(['organization_id', 'status', 'admitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animals');
    }
};
