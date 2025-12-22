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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('supplier_code', 50)->nullable()->unique();
            $table->string('contact_person')->nullable();
            $table->string('mobile', 15)->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('gstin', 15)->nullable()->index();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('taluka_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('village_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address_line', 500)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('ifsc', 20)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('branch')->nullable();
            $table->string('upi_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
