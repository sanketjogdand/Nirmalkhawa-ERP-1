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
        Schema::create('villages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // village name
            $table->foreignId('taluka_id')->constrained('talukas')->onDelete('cascade'); // FK to talukas
            $table->timestamps();
            $table->unique(['name', 'taluka_id']); // No duplicate village in a taluka
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('villages');
    }
};
