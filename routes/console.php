<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Product;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('packing:backfill {names?* : Product names to mark as packing materials}', function (array $names) {
    $names = collect($names)->filter()->values();

    if ($names->isNotEmpty()) {
        $updated = Product::whereIn('name', $names)->update([
            'is_packing' => true,
            'can_purchase' => true,
            'can_consume' => true,
            'can_sell' => false,
            'can_produce' => false,
            'can_stock' => true,
        ]);

        $this->info("Marked {$updated} products as packing materials.");
    }

    $aligned = Product::where('is_packing', true)->update([
        'can_purchase' => true,
        'can_consume' => true,
        'can_sell' => false,
        'can_produce' => false,
        'can_stock' => true,
    ]);

    $this->info("Aligned flags on {$aligned} existing packing products.");
})->purpose('Mark packing materials and align their usage flags');
