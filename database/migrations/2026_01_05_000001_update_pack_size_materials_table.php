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
        if (Schema::hasTable('pack_size_materials') && Schema::hasColumn('pack_size_materials', 'qty_per_pack')) {
            DB::statement('ALTER TABLE pack_size_materials MODIFY qty_per_pack DECIMAL(15,3) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pack_size_materials') && Schema::hasColumn('pack_size_materials', 'qty_per_pack')) {
            DB::statement('ALTER TABLE pack_size_materials MODIFY qty_per_pack DECIMAL(12,3) NOT NULL');
        }
    }
};
