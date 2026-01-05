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
        Schema::table('milk_intakes', function (Blueprint $table) {
            if (! Schema::hasColumn('milk_intakes', 'deleted_at')) {
                $table->softDeletes()->after('locked_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('milk_intakes', function (Blueprint $table) {
            if (Schema::hasColumn('milk_intakes', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
