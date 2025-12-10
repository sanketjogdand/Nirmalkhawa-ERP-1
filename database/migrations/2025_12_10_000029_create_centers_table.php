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
        Schema::create('centers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->nullable()->unique();
            $table->text('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignId('taluka_id')->nullable()->constrained('talukas')->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('villages')->nullOnDelete();
            $table->string('contact_person', 150)->nullable();
            $table->string('mobile', 20);
            $table->string('account_name', 150)->nullable();
            $table->string('account_number', 50)->nullable();
            $table->string('ifsc', 50)->nullable();
            $table->string('branch', 150)->nullable();
            $table->string('status', 10)->default('Active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name']);
            $table->index(['mobile']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centers');
    }
};
