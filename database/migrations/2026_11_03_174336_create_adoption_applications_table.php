<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adoption_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('animal_id')->constrained()->restrictOnDelete();
            $table->foreignId('coordinator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('candidate_name', 160);
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('housing');
            $table->text('candidate_notes')->nullable();
            $table->string('status', 30)->default('new');
            $table->date('adopted_on')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->index(['animal_id', 'status']);
        });
        DB::statement("CREATE UNIQUE INDEX adoption_completed_animal_unique ON adoption_applications (animal_id) WHERE status = 'completed'");
        Schema::create('adoption_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('adoption_application_id')->constrained()->cascadeOnDelete();
            $table->string('author_name');
            $table->text('note')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->index(['adoption_application_id', 'id']);
        });
        Schema::create('adoption_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('adoption_application_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->timestamps();
            $table->index('adoption_application_id');
        });
        Schema::table('care_tasks', function (Blueprint $table): void {
            $table->foreignId('adoption_application_id')->nullable()->constrained()->nullOnDelete();
            $table->index('adoption_application_id');
        });
    }

    public function down(): void
    {
        Schema::table('care_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('adoption_application_id');
        });
        Schema::dropIfExists('adoption_attachments');
        Schema::dropIfExists('adoption_histories');
        Schema::dropIfExists('adoption_applications');
    }
};
