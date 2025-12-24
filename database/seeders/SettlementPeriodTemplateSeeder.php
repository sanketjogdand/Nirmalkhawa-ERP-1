<?php

namespace Database\Seeders;

use App\Models\SettlementPeriodTemplate;
use Illuminate\Database\Seeder;

class SettlementPeriodTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['name' => '1-10', 'start_day' => 1, 'end_day' => 10, 'end_of_month' => false],
            ['name' => '11-20', 'start_day' => 11, 'end_day' => 20, 'end_of_month' => false],
            ['name' => '21-31', 'start_day' => 21, 'end_day' => 31, 'end_of_month' => false],
        ];

        foreach ($defaults as $template) {
            SettlementPeriodTemplate::updateOrCreate(
                ['name' => $template['name']],
                [
                    'start_day' => $template['start_day'],
                    'end_day' => $template['end_day'],
                    'end_of_month' => $template['end_of_month'],
                    'is_active' => true,
                ]
            );
        }
    }
}
