<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's default products.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Raw Milk CM',
                'code' => 'RAW-CM',
                'uom' => 'LTR',
                'can_purchase' => true,
                'can_produce' => false,
                'can_consume' => true,
                'can_sell' => true,
                'can_stock' => true,
                'is_active' => true,
                'category' => 'Milk',
            ],
            [
                'name' => 'Raw Milk BM',
                'code' => 'RAW-BM',
                'uom' => 'LTR',
                'can_purchase' => true,
                'can_produce' => false,
                'can_consume' => true,
                'can_sell' => true,
                'can_stock' => true,
                'is_active' => true,
                'category' => 'Milk',
            ],
            [
                'name' => 'Raw Mix Milk',
                'code' => 'RAW-MIX',
                'uom' => 'LTR',
                'can_purchase' => true,
                'can_produce' => false,
                'can_consume' => true,
                'can_sell' => true,
                'can_stock' => true,
                'is_active' => true,
                'category' => 'Milk',
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['name' => $product['name']],
                $product
            );
        }
    }
}
