<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->string('kind', 30);
            $table->string('title', 160);
            $table->date('occurred_on');
            $table->string('performed_by')->nullable();
            $table->text('description');
            $table->text('recommendations')->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();
            $table->foreignId('correction_of_id')->nullable()->constrained('medical_records')->restrictOnDelete();
            $table->text('correction_reason')->nullable();
            $table->foreignId('care_task_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['animal_id', 'occurred_on', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
