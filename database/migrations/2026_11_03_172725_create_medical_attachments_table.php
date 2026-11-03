<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('medical_record_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('name');
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index('medical_record_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_attachments');
    }
};
