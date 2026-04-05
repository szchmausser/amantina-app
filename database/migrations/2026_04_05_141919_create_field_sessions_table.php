<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('field_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('school_term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('activity_name')->nullable();
            $table->string('location_name')->nullable();
            $table->dateTime('start_datetime');
            $table->dateTime('end_datetime');
            $table->decimal('base_hours', 5, 2)->default(0);
            $table->foreignId('status_id')->constrained('field_session_statuses')->restrictOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('academic_year_id');
            $table->index('school_term_id');
            $table->index('user_id');
            $table->index('status_id');
            $table->index('start_datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('field_sessions');
    }
};
