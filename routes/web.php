<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Center\Form as CenterForm;
use App\Livewire\Center\Show as CenterShow;
use App\Livewire\Center\View as CenterView;
use App\Livewire\MilkIntake\Form as MilkIntakeForm;
use App\Livewire\MilkIntake\View as MilkIntakeView;
use App\Livewire\RateChart\Calculator as RateChartCalculator;
use App\Livewire\RateChart\Form as RateChartForm;
use App\Livewire\RateChart\Show as RateChartShow;
use App\Livewire\RateChart\View as RateChartView;
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

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
