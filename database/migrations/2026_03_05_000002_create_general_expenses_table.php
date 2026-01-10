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
        Schema::create('general_expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->cascadeOnUpdate()->nullOnDelete();
            $table->string('invoice_no', 120)->nullable();
            $table->text('remarks')->nullable();
            $table->string('attachment_path')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->foreignId('locked_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('expense_date');
            $table->index('supplier_id');
            $table->index('is_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_expenses');
    }
};
