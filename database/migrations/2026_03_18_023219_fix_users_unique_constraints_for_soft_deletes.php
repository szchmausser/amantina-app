<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop existing global unique constraints
            $table->dropUnique(['email']);
            $table->dropUnique(['cedula']);
        });

        // Create partial unique indexes for PostgreSQL (where active)
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX users_cedula_unique ON users (cedula) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial unique indexes
        DB::statement('DROP INDEX users_email_unique');
        DB::statement('DROP INDEX users_cedula_unique');

        Schema::table('users', function (Blueprint $table) {
            // Restore global unique constraints
            $table->unique('email');
            $table->unique('cedula');
        });
    }
};
