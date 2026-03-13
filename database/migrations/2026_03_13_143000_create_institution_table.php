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
        Schema::create('institution', function (Blueprint $row) {
            $row->id();
            $row->string('name');
            $row->text('address')->nullable();
            $row->string('email')->nullable();
            $row->string('phone')->nullable();
            $row->string('code')->nullable();
            $row->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institution');
    }
};
