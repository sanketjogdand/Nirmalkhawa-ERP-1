<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Truncating region master tables: " . now());
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::table('villages')->truncate();
        DB::table('talukas')->truncate();
        DB::table('districts')->truncate();
        DB::table('states')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->command->info("Loading JSON...");
        // Adjust path as needed
        $json = file_get_contents(storage_path('app/private/all_villages_final.json'));
        $dataArray = json_decode($json, true);

        $this->command->info("Seeding Data: " . now());
        foreach ($dataArray as $state_name => $state_data) {
            $state_id = DB::table('states')->insertGetId([
                'name' => $state_name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($state_data as $district_name => $district_data) {
                $district_id = DB::table('districts')->insertGetId([
                    'state_id' => $state_id,
                    'name' => $district_name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                foreach ($district_data as $taluka_name => $taluka_data) {
                    $taluka_id = DB::table('talukas')->insertGetId([
                        'district_id' => $district_id,
                        'name' => $taluka_name,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    foreach ($taluka_data as $village_name) {
                        DB::table('villages')->insertOrIgnore([
                            'taluka_id' => $taluka_id,
                            'name' => $village_name,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
            $this->command->info("State Completed: " . $state_name);
        }
        $this->command->info("Region Seeding Done: " . now());
    }
}
