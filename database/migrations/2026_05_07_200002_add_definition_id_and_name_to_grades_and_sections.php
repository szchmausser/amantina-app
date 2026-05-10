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
        Schema::table('grades', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_definition_id')->nullable()->after('academic_year_id');
            $table->string('grade_definition_name', 100)->nullable()->after('grade_definition_id');

            $table->index('grade_definition_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('section_definition_id')->nullable()->after('grade_id');
            $table->string('section_definition_name', 20)->nullable()->after('section_definition_id');

            $table->index('section_definition_id');
        });

        // Backfill existing names into frozen copy columns
        DB::statement('UPDATE grades SET grade_definition_name = name WHERE grade_definition_name IS NULL');
        DB::statement('UPDATE sections SET section_definition_name = name WHERE section_definition_name IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropIndex(['grade_definition_id']);
            $table->dropColumn('grade_definition_id');
            $table->dropColumn('grade_definition_name');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex(['section_definition_id']);
            $table->dropColumn('section_definition_id');
            $table->dropColumn('section_definition_name');
        });
    }
};
