<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\PreferencesController;

Route::get('/', function () {
    return redirect()->route('scans.create');
});

Route::resource('scans', ScanController::class);
Route::get('/scans/{scan}/status', [ScanController::class, 'status'])->name('scans.status');
Route::post('/scans/{scan}/cancel', [ScanController::class, 'cancel'])->name('scans.cancel');
Route::get('/scans/{scan}/export', [ScanController::class, 'export'])->name('scans.export');

Route::post('/preferences/language', [PreferencesController::class, 'updateLanguage'])->name('preferences.language');
Route::post('/preferences/theme', [PreferencesController::class, 'updateTheme'])->name('preferences.theme');
