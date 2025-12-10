<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Center\Form as CenterForm;
use App\Livewire\Center\Show as CenterShow;
use App\Livewire\Center\View as CenterView;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

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

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

require __DIR__.'/auth.php';
