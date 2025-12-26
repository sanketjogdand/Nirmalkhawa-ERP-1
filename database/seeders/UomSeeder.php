<?php

namespace Database\Seeders;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    /**
     * Seed a default UOM list for setup.
     */
    public function run(): void
    {
        Uom::updateOrCreate(['name' => 'LTR']);
        Uom::updateOrCreate(['name' => 'KG']);
        Uom::updateOrCreate(['name' => 'NOS']);
    }
}
