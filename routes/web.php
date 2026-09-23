<?php

use Illuminate\Support\Facades\Route;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\AuditLogController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\DoctorController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\MatrixController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\PermissionController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\RoleController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\SimulatorController;
use SalvatoreCervone\PermissionToolkit\Http\Controllers\UserController;

$prefix = config('permission-toolkit.prefix', 'permission-manager');
$middleware = config('permission-toolkit.middleware', ['web', 'auth']);

Route::group(['prefix' => $prefix, 'middleware' => $middleware, 'as' => 'permission-toolkit.'], function () {
    // Role-Permission Interactive Matrix
    Route::get('/', [MatrixController::class, 'index'])->name('index');
    Route::get('/matrix', [MatrixController::class, 'index'])->name('matrix');
    Route::post('/matrix/toggle', [MatrixController::class, 'toggle'])->name('matrix.toggle');

    // Role & Permission Creation & Deletion
    Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    Route::delete('/roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::post('/permissions', [PermissionController::class, 'store'])->name('permissions.store');
    Route::delete('/permissions/{id}', [PermissionController::class, 'destroy'])->name('permissions.destroy');

    // User Access Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{id}', [UserController::class, 'edit'])->name('users.edit');
    Route::post('/users/{id}', [UserController::class, 'update'])->name('users.update');

    // Diagnostic Simulator
    Route::get('/simulator', [SimulatorController::class, 'index'])->name('simulator');

    // Audit Trail
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit');

    // Integrity Doctor
    Route::get('/doctor', [DoctorController::class, 'index'])->name('doctor');
});
