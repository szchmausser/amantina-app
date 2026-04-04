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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
};
