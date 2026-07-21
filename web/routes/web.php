<?php

use App\Http\Controllers\Admin\AttendanceCorrectionController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\IzinController;
use App\Http\Controllers\Admin\JadwalKerjaController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\LokasiController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('admin.guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.submit');
    });

    Route::middleware('admin.auth')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::get('karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::get('karyawan/create', [KaryawanController::class, 'create'])->name('karyawan.create');
        Route::post('karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::get('karyawan/{id}/edit', [KaryawanController::class, 'edit'])->name('karyawan.edit');
        Route::put('karyawan/{id}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('karyawan/{id}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');

        Route::get('lokasi', [LokasiController::class, 'index'])->name('lokasi.index');
        Route::put('lokasi/{id}', [LokasiController::class, 'update'])->name('lokasi.update');

        Route::get('monitor', [MonitorController::class, 'index'])->name('monitor.index');

        Route::get('izin', [IzinController::class, 'index'])->name('izin.index');
        Route::post('izin/{id}/approve', [IzinController::class, 'approve'])->name('izin.approve');
        Route::post('izin/{id}/reject', [IzinController::class, 'reject'])->name('izin.reject');

        Route::get('shifts', [ShiftController::class, 'index'])->name('shifts.index');
        Route::get('shifts/create', [ShiftController::class, 'create'])->name('shifts.create');
        Route::post('shifts', [ShiftController::class, 'store'])->name('shifts.store');
        Route::get('jadwal', [JadwalKerjaController::class, 'index'])->name('jadwal.index');
        Route::get('jadwal/create', [JadwalKerjaController::class, 'create'])->name('jadwal.create');
        Route::post('jadwal', [JadwalKerjaController::class, 'store'])->name('jadwal.store');
        Route::delete('jadwal/{jadwal}', [JadwalKerjaController::class, 'destroy'])->name('jadwal.destroy');
        Route::get('attendance-corrections', [AttendanceCorrectionController::class, 'index'])->name('corrections.index');
        Route::post('attendance-corrections/{correction}/approve', [AttendanceCorrectionController::class, 'approve'])->name('corrections.approve');
        Route::post('attendance-corrections/{correction}/reject', [AttendanceCorrectionController::class, 'reject'])->name('corrections.reject');

        Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');

    });
});

Route::get('/', function () {
    return redirect()->route('admin.login');
});
