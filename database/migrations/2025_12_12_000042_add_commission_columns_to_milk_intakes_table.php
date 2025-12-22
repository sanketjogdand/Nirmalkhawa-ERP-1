<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milk_intakes', function (Blueprint $table) {
            $table->foreignId('commission_policy_id')->nullable()->after('rate_per_ltr')->constrained('commission_policies');
            $table->decimal('commission_amount', 12, 2)->default(0)->after('commission_policy_id');
            $table->decimal('net_amount', 12, 2)->nullable()->after('commission_amount');
        });
    }

    public function down(): void
    {
        Schema::table('milk_intakes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commission_policy_id');
            $table->dropColumn(['commission_amount', 'net_amount']);
        });
    }
};
