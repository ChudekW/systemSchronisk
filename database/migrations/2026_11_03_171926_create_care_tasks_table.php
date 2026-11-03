<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->string('priority', 20);
            $table->string('status', 20);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['animal_id', 'due_date']);
            $table->index(['assignee_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_tasks');
    }
};
