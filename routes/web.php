<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileSecurityController;
use App\Http\Controllers\RdmController;
use App\Http\Controllers\SimulasiAkiController;
use Illuminate\Support\Facades\Route;

// ---- Public / Guest ----
Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect('/login');
});

// ---- Requires: authenticated + approved ----
Route::middleware(['auth', 'approved'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ---- Simulasi AKI ----
    Route::get('/simulasi/baru', [SimulasiAkiController::class, 'create'])->name('simulasi.create');
    Route::post('/simulasi/baru', [SimulasiAkiController::class, 'store'])->name('simulasi.store');
    Route::get('/simulasi/{simulasi}', [SimulasiAkiController::class, 'show'])->name('simulasi.show');
    Route::post('/simulasi/{simulasi}/complete', [SimulasiAkiController::class, 'completePihakA'])->name('simulasi.complete');
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
    });

    // ---- Profile / Security ----
    Route::get('/profile/security', [ProfileSecurityController::class, 'index'])->name('profile.security');

    // ---- Admin ----
    Route::middleware(['role:admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::post('/users/{user}/approve', [AdminController::class, 'approve'])->name('users.approve');
        Route::post('/users/{user}/reject', [AdminController::class, 'reject'])->name('users.reject');
        Route::put('/users/{user}/role', [AdminController::class, 'updateRole'])->name('users.updateRole');
        Route::get('/rdm', [RdmController::class, 'index'])->name('rdm');
        Route::post('/rdm', [RdmController::class, 'update'])->name('rdm.update');
        Route::get('/audit-log', [AdminController::class, 'auditLog'])->name('audit-log');
    });
});
