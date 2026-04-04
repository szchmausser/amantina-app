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
        // Add a column to store the term type name directly (snapshot at creation time)
        Schema::table('school_terms', function (Blueprint $table) {
            $table->string('term_type_name', 100)->nullable()->after('term_type_id');
        });

        // Populate from existing term_types (PostgreSQL compatible)
        DB::statement('
            UPDATE school_terms
            SET term_type_name = term_types.name
            FROM term_types
            WHERE term_types.id = school_terms.term_type_id
        ');

        // Drop the foreign key constraint
        Schema::table('school_terms', function (Blueprint $table) {
            $table->dropForeign(['term_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add FK constraint
        Schema::table('school_terms', function (Blueprint $table) {
            $table->foreign('term_type_id')
                ->references('id')->on('term_types')
                ->nullOnDelete();
        });

        Schema::table('school_terms', function (Blueprint $table) {
            $table->dropColumn('term_type_name');
        });
    }
};
