<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_intakes', function (Blueprint $table) {
            $table->foreignId('center_settlement_id')
                ->nullable()
                ->after('commission_policy_id')
                ->constrained('center_settlements')
                ->nullOnDelete();

            $table->index('center_settlement_id');
        });
    }

    public function down(): void
    {
        Schema::table('milk_intakes', function (Blueprint $table) {
            $table->dropIndex(['center_settlement_id']);
            $table->dropConstrainedForeignId('center_settlement_id');
        });
    }
};
