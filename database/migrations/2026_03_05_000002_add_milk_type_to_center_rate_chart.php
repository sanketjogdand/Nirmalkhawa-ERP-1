<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! Schema::hasColumn('center_rate_chart', 'milk_type')) {
            Schema::table('center_rate_chart', function (Blueprint $table) use ($driver) {
                if ($driver === 'mysql') {
                    $table->string('milk_type', 3)->after('center_id')->nullable();
                } else {
                    $table->string('milk_type', 3)->nullable();
                }
            });
        }

        if (Schema::hasColumn('center_rate_chart', 'milk_type')) {
            if ($driver === 'mysql') {
                DB::statement('UPDATE center_rate_chart crc JOIN rate_charts rc ON rc.id = crc.rate_chart_id SET crc.milk_type = rc.milk_type WHERE crc.milk_type IS NULL');
                DB::statement('ALTER TABLE center_rate_chart MODIFY milk_type VARCHAR(3) NOT NULL');
            } elseif ($driver === 'pgsql') {
                DB::statement('UPDATE center_rate_chart crc SET milk_type = rc.milk_type FROM rate_charts rc WHERE rc.id = crc.rate_chart_id AND crc.milk_type IS NULL');
                DB::statement('ALTER TABLE center_rate_chart ALTER COLUMN milk_type SET NOT NULL');
            } elseif ($driver === 'sqlite') {
                DB::statement('UPDATE center_rate_chart SET milk_type = (SELECT milk_type FROM rate_charts WHERE rate_charts.id = center_rate_chart.rate_chart_id) WHERE milk_type IS NULL');
            }
        }

        Schema::table('center_rate_chart', function (Blueprint $table) {
            $table->index(['center_id', 'milk_type'], 'center_rate_chart_center_milk_type_idx');
            $table->index(['center_id', 'milk_type', 'effective_from', 'effective_to'], 'center_rate_chart_center_milk_type_dates_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('center_rate_chart', function (Blueprint $table) {
            $table->dropIndex('center_rate_chart_center_milk_type_idx');
            $table->dropIndex('center_rate_chart_center_milk_type_dates_idx');
        });

        if (Schema::hasColumn('center_rate_chart', 'milk_type')) {
            Schema::table('center_rate_chart', function (Blueprint $table) {
                $table->dropColumn('milk_type');
            });
        }
    }
};
