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
        // First, ensure term_types exist with the default data
        $termTypes = [
            ['name' => 'Lapso 1', 'order' => 1, 'is_active' => true],
            ['name' => 'Lapso 2', 'order' => 2, 'is_active' => true],
            ['name' => 'Lapso 3', 'order' => 3, 'is_active' => true],
        ];

        foreach ($termTypes as $type) {
            DB::table('term_types')->updateOrInsert(
                ['name' => $type['name']],
                $type
            );
        }

        // For SQLite compatibility, we need to recreate the table
        // because SQLite can't drop a column that's part of an index
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Get existing data
            $existingTerms = DB::table('school_terms')->get();

            // Create new table without term_number, with term_type_id
            Schema::create('school_terms_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
                $table->foreignId('term_type_id')->nullable()->constrained('term_types')->nullOnDelete();
                $table->date('start_date');
                $table->date('end_date');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['academic_year_id', 'term_type_id']);
            });

            // Map term_number to term_type_id and insert into new table
            foreach ($existingTerms as $term) {
                $termTypeId = null;
                if (isset($term->term_number)) {
                    $termTypeId = DB::table('term_types')->where('order', $term->term_number)->value('id');
                }

                DB::table('school_terms_new')->insert([
                    'id' => $term->id,
                    'academic_year_id' => $term->academic_year_id,
                    'term_type_id' => $termTypeId,
                    'start_date' => $term->start_date,
                    'end_date' => $term->end_date,
                    'created_at' => $term->created_at,
                    'updated_at' => $term->updated_at,
                    'deleted_at' => $term->deleted_at ?? null,
                ]);
            }

            // Drop old table and rename new one
            Schema::drop('school_terms');
            Schema::rename('school_terms_new', 'school_terms');
        } else {
            // For other databases (MySQL, PostgreSQL), use standard approach
            Schema::table('school_terms', function (Blueprint $table) {
                $table->foreignId('term_type_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('term_types')
                    ->nullOnDelete();
            });

            // Migrate existing data: map term_number to term_type_id
            foreach ([1, 2, 3] as $num) {
                $termTypeId = DB::table('term_types')->where('order', $num)->value('id');
                if ($termTypeId) {
                    DB::table('school_terms')
                        ->where('term_number', $num)
                        ->update(['term_type_id' => $termTypeId]);
                }
            }

            // Now drop the old column
            Schema::table('school_terms', function (Blueprint $table) {
                $table->dropColumn('term_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // Recreate table with term_number instead of term_type_id
            $existingTerms = DB::table('school_terms')->get();

            Schema::create('school_terms_new', function (Blueprint $table) {
                $table->id();
                $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
                $table->tinyInteger('term_number')->nullable();
                $table->date('start_date');
                $table->date('end_date');
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['academic_year_id', 'term_number']);
            });

            // Map term_type_id back to term_number
            foreach ($existingTerms as $term) {
                $termNumber = null;
                if (isset($term->term_type_id) && $term->term_type_id) {
                    $termNumber = DB::table('term_types')->where('id', $term->term_type_id)->value('order');
                }

                DB::table('school_terms_new')->insert([
                    'id' => $term->id,
                    'academic_year_id' => $term->academic_year_id,
                    'term_number' => $termNumber,
                    'start_date' => $term->start_date,
                    'end_date' => $term->end_date,
                    'created_at' => $term->created_at,
                    'updated_at' => $term->updated_at,
                    'deleted_at' => $term->deleted_at ?? null,
                ]);
            }

            Schema::drop('school_terms');
            Schema::rename('school_terms_new', 'school_terms');
        } else {
            Schema::table('school_terms', function (Blueprint $table) {
                $table->tinyInteger('term_number')->nullable()->after('id');
            });

            // Restore term_number from term_type_id
            $types = DB::table('term_types')->get();
            foreach ($types as $type) {
                DB::table('school_terms')
                    ->where('term_type_id', $type->id)
                    ->update(['term_number' => $type->order]);
            }

            Schema::table('school_terms', function (Blueprint $table) {
                $table->dropForeign(['term_type_id']);
                $table->dropColumn('term_type_id');
            });
        }
    }
};
