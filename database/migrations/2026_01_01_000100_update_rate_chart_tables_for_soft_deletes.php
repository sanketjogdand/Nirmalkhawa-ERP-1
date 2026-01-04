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
        Schema::table('rate_charts', function (Blueprint $table) {
            if (Schema::hasColumn('rate_charts', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (! Schema::hasColumn('rate_charts', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('rate_chart_slabs', function (Blueprint $table) {
            if (! Schema::hasColumn('rate_chart_slabs', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        Schema::table('center_rate_chart', function (Blueprint $table) {
            if (Schema::hasColumn('center_rate_chart', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (! Schema::hasColumn('center_rate_chart', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rate_charts', function (Blueprint $table) {
            if (! Schema::hasColumn('rate_charts', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (Schema::hasColumn('rate_charts', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('rate_chart_slabs', function (Blueprint $table) {
            if (Schema::hasColumn('rate_chart_slabs', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });

        Schema::table('center_rate_chart', function (Blueprint $table) {
            if (! Schema::hasColumn('center_rate_chart', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (Schema::hasColumn('center_rate_chart', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
