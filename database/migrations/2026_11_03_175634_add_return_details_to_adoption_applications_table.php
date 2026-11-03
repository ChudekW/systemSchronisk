<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adoption_applications', function (Blueprint $table): void {
            $table->date('returned_on')->nullable();
            $table->text('return_reason')->nullable();
            $table->string('received_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('adoption_applications', function (Blueprint $table): void {
            $table->dropColumn(['returned_on', 'return_reason', 'received_by']);
        });
    }
};
