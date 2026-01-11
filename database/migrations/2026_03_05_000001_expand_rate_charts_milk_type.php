<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rate_charts MODIFY milk_type VARCHAR(3)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rate_charts ALTER COLUMN milk_type TYPE VARCHAR(3)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE rate_charts MODIFY milk_type VARCHAR(2)');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE rate_charts ALTER COLUMN milk_type TYPE VARCHAR(2)');
        }
    }
};
