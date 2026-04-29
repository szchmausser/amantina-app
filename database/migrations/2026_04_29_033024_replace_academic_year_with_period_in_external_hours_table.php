<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_hours', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
            // Período libre: ej. "2021-2025" o "2019-2022"
            $table->string('period', 50)->after('admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('external_hours', function (Blueprint $table) {
            $table->dropColumn('period');
            $table->foreignId('academic_year_id')
                ->after('admin_id')
                ->constrained('academic_years')
                ->cascadeOnDelete();
        });
    }
};
