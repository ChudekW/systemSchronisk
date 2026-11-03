<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('care_task_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('care_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name');
            $table->text('note')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['care_task_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('care_task_histories');
    }
};
