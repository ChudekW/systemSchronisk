<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('organization_role', 30)->default('caretaker');
            $table->boolean('is_active')->default(true);
            $table->string('invitation_token', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('invitation_accepted_at')->nullable();
        });
        DB::table('users')->where('is_organization_admin', true)->update(['organization_role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['organization_role', 'is_active', 'invitation_token', 'invited_at', 'invitation_expires_at', 'invitation_accepted_at']);
        });
    }
};
