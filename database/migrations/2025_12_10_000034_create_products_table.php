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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable()->unique();
            $table->string('uom', 20);
            $table->string('hsn_code', 50)->nullable();
            $table->decimal('default_gst_rate', 5, 2)->nullable();
            $table->string('category', 100)->nullable();
            $table->boolean('is_packing')->default(false);
            $table->boolean('can_purchase')->default(true);
            $table->boolean('can_produce')->default(true);
            $table->boolean('can_consume')->default(true);
            $table->boolean('can_sell')->default(true);
            $table->boolean('can_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
