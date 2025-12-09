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
        Schema::create('talukas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // taluka name
            $table->foreignId('district_id')->constrained('districts')->onDelete('cascade'); // FK to districts
            $table->timestamps();
            $table->unique(['name', 'district_id']); // No duplicate taluka in a district
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('talukas');
    }
};
