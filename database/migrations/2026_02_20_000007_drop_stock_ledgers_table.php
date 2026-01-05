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
        Schema::dropIfExists('stock_ledgers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->dateTime('txn_datetime');
            $table->string('txn_type', 50);
            $table->boolean('is_increase')->default(true);
            $table->decimal('qty', 15, 3);
            $table->string('uom', 30)->nullable();
            $table->decimal('rate', 15, 4)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('remarks', 1000)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'txn_datetime']);
            $table->index('txn_type');
            $table->index(['reference_type', 'reference_id']);
        });
    }
};
