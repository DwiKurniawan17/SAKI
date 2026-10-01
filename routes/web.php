<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\SimulasiAkiController;
use Illuminate\Support\Facades\Route;

// ---- Public / Guest ----
Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

// DIHAPUS: Route::get('/logout') — rentan CSRF (siapapun bisa logout user via link)
// Logout ditangani oleh Laravel Fortify via POST /logout dengan CSRF token.
// Pastikan semua tombol logout menggunakan form POST (lihat layout.blade.php).

// ---- Requires: authenticated + approved ----
Route::middleware(['auth', 'approved'])->group(function () {

    // ---- Profile / Security (dapat diakses sebelum 2FA aktif untuk proses konfigurasi) ----
    Route::get('/profile/security', [ProfileSecurityController::class, 'index'])->name('profile.security');
    Route::post('/profile/security/2fa/enable', [ProfileSecurityController::class, 'enable2fa'])->name('profile.security.2fa.enable');
    Route::post('/profile/security/2fa/confirm', [ProfileSecurityController::class, 'confirm2fa'])->name('profile.security.2fa.confirm');
    Route::post('/profile/security/2fa/cancel', [ProfileSecurityController::class, 'cancel2faSetup'])->name('profile.security.2fa.cancel');
    Route::post('/profile/security/2fa/disable', [ProfileSecurityController::class, 'disable2fa'])->name('profile.security.2fa.disable');
    Route::post('/profile/security/2fa/recovery-codes', [ProfileSecurityController::class, 'regenerateRecoveryCodes'])->name('profile.security.2fa.recovery-codes');

    // ---- Fitur Operasional (Wajib 2FA bagi role admin dan engineering_capex) ----
    Route::middleware(['ensure2fa'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // ---- Simulasi AKI ----
        Route::get('/simulasi/baru', [SimulasiAkiController::class, 'create'])->name('simulasi.create');
        Route::post('/simulasi/baru', [SimulasiAkiController::class, 'store'])->name('simulasi.store');
        Route::post('/simulasi/import-excel', [SimulasiAkiController::class, 'storeExcel'])->name('simulasi.storeExcel');
        Route::get('/simulasi/{simulasi}', [SimulasiAkiController::class, 'show'])->name('simulasi.show');
        Route::post('/simulasi/{simulasi}/complete-marketing', [SimulasiAkiController::class, 'completePihakB'])->name('simulasi.completeMarketing');
        Route::get('/simulasi/{simulasi}/download', [SimulasiAkiController::class, 'download'])->name('simulasi.download');
        Route::delete('/simulasi/{simulasi}', [SimulasiAkiController::class, 'destroy'])->name('simulasi.destroy');
        Route::get('/riwayat', [SimulasiAkiController::class, 'riwayat'])->name('simulasi.riwayat');

        // ---- Revisi (user edit setelah direject RSO) ----
        Route::get('/simulasi/{simulasi}/edit', [SimulasiAkiController::class, 'edit'])->name('simulasi.edit');
        Route::put('/simulasi/{simulasi}', [SimulasiAkiController::class, 'update'])->name('simulasi.update');

        // ---- RSO Review (admin only) ----
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/simulasi/{simulasi}/approve', [SimulasiAkiController::class, 'approve'])->name('simulasi.approve');
            Route::post('/simulasi/{simulasi}/reject', [SimulasiAkiController::class, 'reject'])->name('simulasi.reject');
            Route::post('/simulasi/{simulasi}/recalculate', [SimulasiAkiController::class, 'recalculate'])->name('simulasi.recalculate');
            Route::match(['get', 'post'], '/simulasi/{simulasi}/preview-excel', [SimulasiAkiController::class, 'previewExcel'])->name('simulasi.previewExcel');
        });

        // ---- API Price List Lookup (untuk Marketing / Form) ----
        Route::get('/api/price-list/lookup', [PriceListController::class, 'apiLookup'])->name('price-list.lookup');
        Route::get('/api/price-list/all', [PriceListController::class, 'apiAll'])->name('price-list.all');

        // ---- Admin ----
        Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [AdminController::class, 'users'])->name('users');
            Route::post('/users/{user}/approve', [AdminController::class, 'approve'])->name('users.approve');
            Route::post('/users/{user}/reject', [AdminController::class, 'reject'])->name('users.reject');
            Route::put('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.updateRole');
            Route::get('/audit-log', [AdminController::class, 'auditLog'])->name('audit-log');

            // Price List Management
            Route::get('/price-list', [PriceListController::class, 'index'])->name('price-list');
            Route::post('/price-list/import', [PriceListController::class, 'import'])->name('price-list.import');
            Route::post('/price-list/clear', [PriceListController::class, 'clearAll'])->name('price-list.clear');
            Route::delete('/price-list/{priceList}', [PriceListController::class, 'destroy'])->name('price-list.destroy');
        });
    });
});
