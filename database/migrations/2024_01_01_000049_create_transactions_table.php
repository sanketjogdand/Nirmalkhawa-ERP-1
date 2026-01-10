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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('type')->nullable(); // 'salary', 'purchase', 'sales', 'expense', 'gst', etc.
            $table->string('other_type')->nullable(); // 'salary', 'purchase', 'sales', 'expense', 'gst', etc.
            $table->string('reference')->nullable(); // Reference to source (eg: Invoice No, Salary Payment ID)

            $table->string('from_party_type')->nullable(); // App\Models\SalaryPayment, App\Models\RawMaterialStock, etc.
            $table->string('to_party_type')->nullable(); // App\Models\SalaryPayment, App\Models\RawMaterialStock, etc.
            $table->string('from_party')->nullable();
            $table->string('to_party')->nullable();
            $table->string('payment_mode')->nullable();

            $table->string('gst_type')->nullable(); // input, output, nil, etc.
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('gst_percent', 5, 2)->nullable();
            $table->decimal('gst_amount', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->decimal('paid_amount', 12, 2)->nullable();
            $table->string('gstin')->nullable(); // Vendor/customer GSTIN
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
