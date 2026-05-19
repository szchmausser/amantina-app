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
        // Drop existing global unique constraints
        Schema::table('section_definitions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('grade_definitions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('health_conditions', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('activity_categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        Schema::table('academic_years', function (Blueprint $table) {
            $table->dropUnique(['name']);
        });
        // Note: school_terms has no existing unique index on (academic_year_id, term_type_id)
        // in PostgreSQL — the migration that added term_type_id only created it for SQLite.
        Schema::table('grades', function (Blueprint $table) {
            $table->dropUnique(['academic_year_id', 'name']);
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->dropUnique(['academic_year_id', 'grade_id', 'name']);
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'academic_year_id']);
        });
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->dropUnique(['academic_year_id', 'section_id', 'user_id']);
        });

        // Create partial unique indexes for PostgreSQL (where not soft-deleted)
        DB::statement('CREATE UNIQUE INDEX section_definitions_name_unique ON section_definitions (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX grade_definitions_name_unique ON grade_definitions (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX locations_name_unique ON locations (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX health_conditions_name_unique ON health_conditions (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX activity_categories_name_unique ON activity_categories (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX academic_years_name_unique ON academic_years (name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX school_terms_academic_year_id_term_type_id_unique ON school_terms (academic_year_id, term_type_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX grades_academic_year_id_name_unique ON grades (academic_year_id, name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX sections_academic_year_id_grade_id_name_unique ON sections (academic_year_id, grade_id, name) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX enrollments_user_id_academic_year_id_unique ON enrollments (user_id, academic_year_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX teacher_assignments_academic_year_id_section_id_user_id_unique ON teacher_assignments (academic_year_id, section_id, user_id) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial unique indexes
        DB::statement('DROP INDEX section_definitions_name_unique');
        DB::statement('DROP INDEX grade_definitions_name_unique');
        DB::statement('DROP INDEX locations_name_unique');
        DB::statement('DROP INDEX health_conditions_name_unique');
        DB::statement('DROP INDEX activity_categories_name_unique');
        DB::statement('DROP INDEX academic_years_name_unique');
        DB::statement('DROP INDEX school_terms_academic_year_id_term_type_id_unique');
        DB::statement('DROP INDEX grades_academic_year_id_name_unique');
        DB::statement('DROP INDEX sections_academic_year_id_grade_id_name_unique');
        DB::statement('DROP INDEX enrollments_user_id_academic_year_id_unique');
        DB::statement('DROP INDEX teacher_assignments_academic_year_id_section_id_user_id_unique');

        // Restore global unique constraints
        Schema::table('section_definitions', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('grade_definitions', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('health_conditions', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('activity_categories', function (Blueprint $table) {
            $table->unique('name');
        });
        Schema::table('academic_years', function (Blueprint $table) {
            $table->unique('name');
        });
        // Note: school_terms never had a global unique on (academic_year_id, term_type_id)
        // in PostgreSQL, so nothing to restore here.
        Schema::table('grades', function (Blueprint $table) {
            $table->unique(['academic_year_id', 'name']);
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->unique(['academic_year_id', 'grade_id', 'name']);
        });
        Schema::table('enrollments', function (Blueprint $table) {
            $table->unique(['user_id', 'academic_year_id']);
        });
        Schema::table('teacher_assignments', function (Blueprint $table) {
            $table->unique(['academic_year_id', 'section_id', 'user_id']);
        });
    }
};
