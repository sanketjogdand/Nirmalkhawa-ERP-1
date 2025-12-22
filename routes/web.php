<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Center\Form as CenterForm;
use App\Livewire\Center\Show as CenterShow;
use App\Livewire\Center\View as CenterView;
use App\Livewire\Customer\Form as CustomerForm;
use App\Livewire\Customer\Show as CustomerShow;
use App\Livewire\Customer\View as CustomerView;
use App\Livewire\MilkIntake\Form as MilkIntakeForm;
use App\Livewire\MilkIntake\View as MilkIntakeView;
use App\Livewire\Product\Form as ProductForm;
use App\Livewire\Product\View as ProductView;
use App\Livewire\Inventory\StockAdjustment as InventoryStockAdjustment;
use App\Livewire\Inventory\StockLedger as InventoryStockLedger;
use App\Livewire\Inventory\StockSummary as InventoryStockSummary;
use App\Livewire\Inventory\TransferToMix as InventoryTransferToMix;
use App\Livewire\Packing\History as PackHistory;
use App\Livewire\Packing\InventorySummary as PackInventory;
use App\Livewire\Packing\PackSizes;
use App\Livewire\Packing\PackingForm;
use App\Livewire\Packing\UnpackingForm;
use App\Livewire\Production\Form as ProductionForm;
use App\Livewire\Production\Show as ProductionShow;
use App\Livewire\Production\View as ProductionView;
use App\Livewire\Dispatch\View as DispatchView;
use App\Livewire\Dispatch\Form as DispatchForm;
use App\Livewire\Dispatch\Show as DispatchShow;
use App\Livewire\RateChart\Calculator as RateChartCalculator;
use App\Livewire\RateChart\Form as RateChartForm;
use App\Livewire\RateChart\Show as RateChartShow;
use App\Livewire\RateChart\View as RateChartView;
use App\Livewire\CommissionPolicy\View as CommissionPolicyView;
use App\Livewire\CommissionPolicy\Form as CommissionPolicyForm;
use App\Livewire\CommissionPolicy\Assign as CommissionAssignment;
use App\Livewire\Recipe\Form as RecipeForm;
use App\Livewire\Recipe\Show as RecipeShow;
use App\Livewire\Recipe\View as RecipeView;
use App\Livewire\SalesInvoice\Form as SalesInvoiceForm;
use App\Livewire\SalesInvoice\Show as SalesInvoiceShow;
use App\Livewire\SalesInvoice\View as SalesInvoiceView;
use App\Livewire\Setup;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::middleware('permission:ratechart.view')->group(function () {
        Route::get('rate-charts', RateChartView::class)->name('rate-charts.view');
        Route::get('rate-charts/calculator', RateChartCalculator::class)->name('rate-charts.calculator');
        Route::get('rate-charts/{rateChart}', RateChartShow::class)
            ->whereNumber('rateChart')
            ->name('rate-charts.show');
    });

    Route::middleware('permission:commissionpolicy.view')->group(function () {
        Route::get('commission-policies', CommissionPolicyView::class)->name('commission-policies.view');
    });
    Route::get('commission-policies/create', CommissionPolicyForm::class)
        ->middleware('permission:commissionpolicy.create')
        ->name('commission-policies.create');
    Route::get('commission-policies/{policy}/edit', CommissionPolicyForm::class)
        ->middleware('permission:commissionpolicy.update')
        ->whereNumber('policy')
        ->name('commission-policies.edit');
    Route::get('commission-assignments', CommissionAssignment::class)
        ->middleware('permission:commissionassignment.view')
        ->name('commission-assignments');

    Route::get('rate-charts/create', RateChartForm::class)
        ->middleware('permission:ratechart.create')
        ->name('rate-charts.create');

    Route::get('rate-charts/{rateChart}/edit', RateChartForm::class)
        ->middleware('permission:ratechart.update')
        ->whereNumber('rateChart')
        ->name('rate-charts.edit');

    Route::get('centers/create', CenterForm::class)
        ->middleware('permission:center.create')
        ->name('centers.create');

    Route::middleware('permission:center.view')->group(function () {
        Route::get('centers', CenterView::class)->name('centers.view');
        Route::get('centers/{center}', CenterShow::class)
            ->whereNumber('center')
            ->name('centers.show');
    });

    Route::get('centers/{center}/edit', CenterForm::class)
        ->middleware('permission:center.update')
        ->whereNumber('center')
        ->name('centers.edit');

    Route::middleware('permission:milkintake.view')->group(function () {
        Route::get('milk-intakes', MilkIntakeView::class)->name('milk-intakes.view');
    });

    Route::get('milk-intakes/create', MilkIntakeForm::class)
        ->middleware('permission:milkintake.create')
        ->name('milk-intakes.create');

    Route::get('milk-intakes/{milkIntake}/edit', MilkIntakeForm::class)
        ->middleware('permission:milkintake.update')
        ->whereNumber('milkIntake')
        ->name('milk-intakes.edit');

    Route::middleware('permission:product.view')->group(function () {
        Route::get('products', ProductView::class)->name('products.view');
    });

    Route::get('products/create', ProductForm::class)
        ->middleware('permission:product.create')
        ->name('products.create');

    Route::get('products/{product}/edit', ProductForm::class)
        ->middleware('permission:product.update')
        ->whereNumber('product')
        ->name('products.edit');

    Route::middleware('permission:customer.view')->group(function () {
        Route::get('customers', CustomerView::class)->name('customers.view');
        Route::get('customers/{customer}', CustomerShow::class)
            ->whereNumber('customer')
            ->name('customers.show');
    });

    Route::get('customers/create', CustomerForm::class)
        ->middleware('permission:customer.create')
        ->name('customers.create');

    Route::get('customers/{customer}/edit', CustomerForm::class)
        ->middleware('permission:customer.update')
        ->whereNumber('customer')
        ->name('customers.edit');

    Route::middleware('permission:recipe.view')->group(function () {
        Route::get('recipes', RecipeView::class)->name('recipes.view');
        Route::get('recipes/{recipe}', RecipeShow::class)
            ->middleware('permission:recipe.view')
            ->whereNumber('recipe')
            ->name('recipes.show');
    });

    Route::get('recipes/create', RecipeForm::class)
        ->middleware('permission:recipe.create')
        ->name('recipes.create');

    Route::get('recipes/{recipe}/edit', RecipeForm::class)
        ->middleware('permission:recipe.update')
        ->whereNumber('recipe')
        ->name('recipes.edit');

    Route::middleware('permission:inventory.view')->group(function () {
        Route::get('inventory/stock-summary', InventoryStockSummary::class)->name('inventory.stock-summary');
        Route::get('inventory/stock-ledger', InventoryStockLedger::class)->name('inventory.stock-ledger');
    });

    Route::get('inventory/stock-adjustments', InventoryStockAdjustment::class)
        ->middleware('permission:inventory.adjust')
        ->name('inventory.stock-adjustments');

    Route::get('inventory/transfer-to-mix', InventoryTransferToMix::class)
        ->middleware('permission:inventory.transfer')
        ->name('inventory.transfer-to-mix');

    Route::get('pack-sizes', PackSizes::class)
        ->middleware('permission:packsize.view')
        ->name('pack-sizes');

    Route::get('packing', PackingForm::class)
        ->middleware('permission:packing.create')
        ->name('packing');

    Route::get('unpacking', UnpackingForm::class)
        ->middleware('permission:unpacking.create')
        ->name('unpacking');

    Route::get('pack-inventory', PackInventory::class)
        ->middleware('permission:packinventory.view')
        ->name('pack-inventory');

    Route::get('pack-history', PackHistory::class)
        ->middleware('permission:packinventory.view')
        ->name('pack-history');

    Route::get('dispatches/create', DispatchForm::class)
        ->middleware('permission:dispatch.create')
        ->name('dispatches.create');

    Route::get('dispatches/{dispatch}/edit', DispatchForm::class)
        ->middleware('permission:dispatch.update')
        ->whereNumber('dispatch')
        ->name('dispatches.edit');

    Route::middleware('permission:dispatch.view')->group(function () {
        Route::get('dispatches', DispatchView::class)->name('dispatches.view');
        Route::get('dispatches/{dispatch}', DispatchShow::class)
            ->middleware('permission:dispatch.view')
            ->whereNumber('dispatch')
            ->name('dispatches.show');
    });

    Route::get('sales-invoices/create', SalesInvoiceForm::class)
        ->middleware('permission:salesinvoice.create')
        ->name('sales-invoices.create');

    Route::get('sales-invoices/{salesInvoice}/edit', SalesInvoiceForm::class)
        ->middleware('permission:salesinvoice.update')
        ->whereNumber('salesInvoice')
        ->name('sales-invoices.edit');

    Route::middleware('permission:salesinvoice.view')->group(function () {
        Route::get('sales-invoices', SalesInvoiceView::class)->name('sales-invoices.view');
        Route::get('sales-invoices/{salesInvoice}', SalesInvoiceShow::class)
            ->middleware('permission:salesinvoice.view')
            ->whereNumber('salesInvoice')
            ->name('sales-invoices.show');
    });

    Route::middleware('permission:production.view')->group(function () {
        Route::get('productions', ProductionView::class)->name('productions.view');
    });

    Route::get('productions/create', ProductionForm::class)
        ->middleware('permission:production.create')
        ->name('productions.create');

    Route::get('productions/{production}', ProductionShow::class)
        ->middleware('permission:production.view')
        ->whereNumber('production')
        ->name('productions.show');

    Route::get('productions/{production}/edit', ProductionForm::class)
        ->middleware('permission:production.update')
        ->whereNumber('production')
        ->name('productions.edit');

    Route::get('/setup', Setup::class)->name('setup');
    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
