<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\OperationLogController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 公开接口，无需认证
|
*/

// 认证接口
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

/*
|--------------------------------------------------------------------------
| 需要认证的接口
|
*/
Route::middleware(['auth:api'])->group(function () {
    // 认证相关
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::get('/me', [AuthController::class, 'me']);
    });

    // 用户管理
    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])->middleware('permission:user:list');
        Route::post('/', [UserController::class, 'store'])->middleware('permission:user:create');
        Route::get('/{id}', [UserController::class, 'show'])->middleware('permission:user:list');
        Route::put('/{id}', [UserController::class, 'update'])->middleware('permission:user:edit');
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('permission:user:delete');
        Route::put('/{id}/status', [UserController::class, 'toggleStatus'])->middleware('permission:user:edit');
        Route::post('/change-password', [UserController::class, 'changePassword']);
    });

    // 角色管理
    Route::prefix('roles')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->middleware('permission:role:list');
        Route::get('/all', [RoleController::class, 'all']);
        Route::post('/', [RoleController::class, 'store'])->middleware('permission:role:create');
        Route::get('/{id}', [RoleController::class, 'show'])->middleware('permission:role:list');
        Route::put('/{id}', [RoleController::class, 'update'])->middleware('permission:role:edit');
        Route::delete('/{id}', [RoleController::class, 'destroy'])->middleware('permission:role:delete');
        Route::get('/{id}/permissions', [RoleController::class, 'permissions'])->middleware('permission:role:list');
        Route::put('/{id}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:role:edit');
    });

    // 权限管理
    Route::prefix('permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->middleware('permission:permission:list');
        Route::get('/all', [PermissionController::class, 'all']);
        Route::post('/', [PermissionController::class, 'store'])->middleware('permission:permission:create');
        Route::get('/{id}', [PermissionController::class, 'show'])->middleware('permission:permission:list');
        Route::put('/{id}', [PermissionController::class, 'update'])->middleware('permission:permission:edit');
        Route::delete('/{id}', [PermissionController::class, 'destroy'])->middleware('permission:permission:delete');
    });

    // 操作日志
    Route::prefix('operation-logs')->group(function () {
        Route::get('/', [OperationLogController::class, 'index'])->middleware('permission:log:list');
        Route::get('/modules', [OperationLogController::class, 'modules'])->middleware('permission:log:list');
        Route::get('/actions', [OperationLogController::class, 'actions'])->middleware('permission:log:list');
        Route::get('/{id}', [OperationLogController::class, 'show'])->middleware('permission:log:detail');
        Route::delete('/{id}', [OperationLogController::class, 'destroy'])->middleware('permission:log:delete');
        Route::delete('/batch', [OperationLogController::class, 'batchDestroy'])->middleware('permission:log:delete');
        Route::post('/clean', [OperationLogController::class, 'clean'])->middleware('permission:log:delete');
        Route::get('/export', [OperationLogController::class, 'export'])->middleware('permission:log:export');
    });
});
