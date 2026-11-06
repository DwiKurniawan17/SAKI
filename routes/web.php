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
// ---- Email Verification with OTP Code ----
Route::middleware(['auth'])->group(function () {
    Route::post('/email/verify-code', [\App\Http\Controllers\Auth\VerifyEmailCodeController::class, 'verify'])
        ->name('verification.verify-code');
});

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
        Route::get('/simulasi/{simulasi}/download-word', [SimulasiAkiController::class, 'downloadWord'])->name('simulasi.downloadWord');
        Route::delete('/simulasi/{simulasi}', [SimulasiAkiController::class, 'destroy'])->name('simulasi.destroy');
        Route::get('/riwayat', [SimulasiAkiController::class, 'riwayat'])->name('simulasi.riwayat');

        // ---- Studio Simulasi Interaktif (React SPA - Khusus RSO / Admin) ----
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/simulasi-studio', [SimulasiAkiController::class, 'studio'])->name('simulasi.studio');
            Route::get('/simulasi/{simulasi}/studio', [SimulasiAkiController::class, 'studioWithData'])->name('simulasi.studio.edit');
            Route::post('/api/simulasi/calculate-live', [SimulasiAkiController::class, 'calculateLive'])->name('simulasi.calculateLive');
            Route::post('/api/simulasi/save-studio', [SimulasiAkiController::class, 'saveStudio'])->name('simulasi.saveStudio');
        });

        // ---- Revisi (user edit setelah direject RSO) ----
        Route::get('/simulasi/{simulasi}/edit', [SimulasiAkiController::class, 'edit'])->name('simulasi.edit');
        Route::put('/simulasi/{simulasi}', [SimulasiAkiController::class, 'update'])->name('simulasi.update');

        // ---- RSO Review (admin only) ----
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/simulasi/{simulasi}/approve', [SimulasiAkiController::class, 'approve'])->name('simulasi.approve');
            Route::post('/simulasi/{simulasi}/reject', [SimulasiAkiController::class, 'reject'])->name('simulasi.reject');
            Route::post('/simulasi/{simulasi}/recalculate', [SimulasiAkiController::class, 'recalculate'])->name('simulasi.recalculate');
            Route::match(['get', 'post'], '/simulasi/{simulasi}/preview-excel', [SimulasiAkiController::class, 'previewExcel'])->name('simulasi.previewExcel');
            Route::match(['get', 'post'], '/simulasi/{simulasi}/preview-word', [SimulasiAkiController::class, 'previewWord'])->name('simulasi.previewWord');
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
            Route::post('/price-list/delete-filtered', [PriceListController::class, 'deleteFiltered'])->name('price-list.delete-filtered');
            Route::patch('/price-list/{priceList}', [PriceListController::class, 'update'])->name('price-list.update');
            Route::delete('/price-list/{priceList}', [PriceListController::class, 'destroy'])->name('price-list.destroy');
            Route::post('/parameter/otc', [PriceListController::class, 'updateDefaultOtc'])->name('parameter.update-otc');
        });
    });
});
