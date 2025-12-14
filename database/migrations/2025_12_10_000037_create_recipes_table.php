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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('output_product_id')->constrained('products');
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->boolean('is_active')->default(false);
            $table->decimal('output_qty', 15, 3);
            $table->string('output_uom', 20);
            $table->string('notes', 1000)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['output_product_id', 'version', 'deleted_at'], 'recipes_product_version_unique');
            $table->index(['output_product_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
