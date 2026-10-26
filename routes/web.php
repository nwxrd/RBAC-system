<?php

use App\Modules\ModuleCatalog;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    foreach (ModuleCatalog::definitions() as $module) {
        require $module->routeFile();
    }
});

require __DIR__.'/settings.php';
