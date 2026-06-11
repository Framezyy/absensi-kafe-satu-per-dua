<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KaryawanController;
use App\Http\Controllers\Admin\LokasiController;
use App\Http\Controllers\Admin\MonitorController;
use App\Http\Controllers\Admin\IzinController;
use App\Http\Controllers\Admin\BonusController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\AuditController;

Route::prefix("admin")->name("admin.")->group(function () {
    Route::middleware("admin.guest")->group(function () {
        Route::get("login", [AuthController::class, "showLogin"])->name("login");
        Route::post("login", [AuthController::class, "login"])->name("login.submit");
    });

    Route::middleware("admin.auth")->group(function () {
        Route::get("dashboard", [DashboardController::class, "index"])->name("dashboard");
        Route::post("logout", [AuthController::class, "logout"])->name("logout");

        Route::get("karyawan", [KaryawanController::class, "index"])->name("karyawan.index");
        Route::get("karyawan/create", [KaryawanController::class, "create"])->name("karyawan.create");
        Route::post("karyawan", [KaryawanController::class, "store"])->name("karyawan.store");
        Route::get("karyawan/{id}/edit", [KaryawanController::class, "edit"])->name("karyawan.edit");
        Route::put("karyawan/{id}", [KaryawanController::class, "update"])->name("karyawan.update");

        Route::get("lokasi", [LokasiController::class, "index"])->name("lokasi.index");
        Route::put("lokasi/{id}", [LokasiController::class, "update"])->name("lokasi.update");

        Route::get("monitor", [MonitorController::class, "index"])->name("monitor.index");

        Route::get("izin", [IzinController::class, "index"])->name("izin.index");
        Route::post("izin/{id}/approve", [IzinController::class, "approve"])->name("izin.approve");
        Route::post("izin/{id}/reject", [IzinController::class, "reject"])->name("izin.reject");

        Route::get("bonus", [BonusController::class, "index"])->name("bonus.index");
        Route::get("bonus/create", [BonusController::class, "create"])->name("bonus.create");
        Route::post("bonus", [BonusController::class, "store"])->name("bonus.store");

        Route::get("payroll", [PayrollController::class, "index"])->name("payroll.index");

        Route::get("audit", [AuditController::class, "index"])->name("audit.index");
    });
});

Route::get("/", function () { return redirect()->route("admin.login"); });