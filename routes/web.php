<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\SimulasiAkiController;
use Illuminate\Support\Facades\Route;

// ---- Public / Guest ----
Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
});

// ---- Requires: authenticated + approved ----
Route::middleware(['auth', 'approved'])->group(function () {

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

    // ---- Profile / Security ----
    Route::get('/profile/security', [ProfileSecurityController::class, 'index'])->name('profile.security');

    // ---- Admin ----
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/approve', [AdminController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminController::class, 'reject'])->name('users.reject');
        Route::put('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.updateRole');
        Route::get('/audit-log', [AdminController::class, 'auditLog'])->name('audit-log');
    });
});
