<?php

use Illuminate\Support\Facades\Route;

// Controllers - Auth & Admin
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KriteriaController;
use App\Http\Controllers\PeriodeController;
use App\Http\Controllers\PenilaianController;
use App\Http\Controllers\SawController;

// Controllers - Pimpinan
use App\Http\Controllers\Pimpinan\PimpinanDashboardController;
use App\Http\Controllers\Pimpinan\PimpinanKaryawanController;
use App\Http\Controllers\Pimpinan\PimpinanLaporanController;

/*
|--------------------------------------------------------------------------
| Redirect Default
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| RUTE GUEST (Belum Login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->controller(LoginController::class)->group(function () {
    Route::get('/login', 'Showlogin')->name('login');
    Route::post('/login', 'login');
});

/*
|--------------------------------------------------------------------------
| RUTE TERPROTEKSI (Wajib Login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    
    // Logout (Harus login dulu baru bisa logout)
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // ==========================================
    // 1. PANEL ADMIN / HRD
    // ==========================================
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Master Data
    Route::resource('karyawan', KaryawanController::class)->except(['create', 'show', 'edit']);
    Route::get('/karyawan/{nip}', [KaryawanController::class, 'show'])->name('karyawan.show');
    
    Route::resource('kriteria', KriteriaController::class)->except(['create', 'show', 'edit']);
    Route::resource('periode', PeriodeController::class)->except(['create', 'show', 'edit']);

    // Transaksi Penilaian (Input Data Absensi)
    Route::resource('penilaian', PenilaianController::class);
    Route::post('/penilaian/import', [PenilaianController::class, 'import'])->name('penilaian.import');
    Route::get('/penilaian/download-template', [PenilaianController::class, 'downloadTemplate'])->name('penilaian.template');
    Route::delete('/penilaian/periode/{periodeId}', [App\Http\Controllers\PenilaianController::class, 'destroyByPeriode']);

    // Proses Perhitungan SAW & Ekspor
    Route::prefix('saw')->name('saw.')->controller(SawController::class)->group(function () {
        Route::get('/proses', 'index')->name('proses');
        Route::post('/simpan', 'simpan')->name('simpan');
        Route::get('/hasil', 'hasil')->name('hasil');
        Route::get('/export/pdf', 'exportPdf')->name('export.pdf');
        Route::get('/export/excel', 'exportExcel')->name('export.excel');
    });

    // ==========================================
    // 2. PANEL PIMPINAN
    // ==========================================
    Route::prefix('pimpinan')->name('pimpinan.')->middleware('pimpinan')->group(function () {
        
        // Dashboard Pimpinan
        Route::get('/dashboard', [PimpinanDashboardController::class, 'index'])->name('dashboard');

        // Data Karyawan (Pimpinan)
        Route::prefix('karyawan')->controller(PimpinanKaryawanController::class)->group(function () {
            Route::get('/', 'index')->name('karyawan');
            Route::get('/{nip}', 'show')->name('karyawan.show');
        });

        // Laporan & Export (Pimpinan)
        Route::prefix('laporan')->controller(PimpinanLaporanController::class)->group(function () {
            Route::get('/', 'index')->name('laporan');
            Route::get('/export/pdf', 'exportPdf')->name('laporan.pdf');
            Route::get('/export/excel', 'exportExcel')->name('laporan.excel');
        });
        
    });

});