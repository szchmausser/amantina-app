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
        Schema::create('student_representatives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('representative_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('relationship_type_id')->constrained('relationship_types');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['student_id', 'representative_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_representatives');
    }
};
