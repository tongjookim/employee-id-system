<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\DesignTemplateController;
use App\Http\Controllers\User\AuthController as UserAuthController;
use App\Http\Controllers\User\IdCardController;
use App\Http\Controllers\Api\QrVerifyController;

// ── 홈 ──
Route::get('/', fn () => redirect()->route('user.login'));

// ══════════════════════════════════════════════
// 관리자 (Admin)
// ══════════════════════════════════════════════
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('logout',[AdminAuthController::class, 'logout'])->name('logout');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // ★ download-template을 resource 위에 배치 (라우트 충돌 방지)
        Route::get('employees/download-template', [EmployeeController::class, 'downloadTemplate'])->name('employees.download-template');
        Route::post('employees/import-excel', [EmployeeController::class, 'importExcel'])->name('employees.import-excel');

        // 직원 관리
        Route::resource('employees', EmployeeController::class)->except('show');
        Route::post('employees/{employee}/regenerate-qr', [EmployeeController::class, 'regenerateQr'])->name('employees.regenerate-qr');
        Route::get('employees/{employee}/preview', [EmployeeController::class, 'previewIdCard'])->name('employees.preview');

        // 디자인 템플릿 관리
        Route::resource('templates', DesignTemplateController::class)->except('show');
        Route::get('templates/{template}/mapping', [DesignTemplateController::class, 'mapping'])->name('templates.mapping');
        Route::post('templates/{template}/mapping', [DesignTemplateController::class, 'saveMappings'])->name('templates.save-mappings');
    });
});

// ══════════════════════════════════════════════
// 사용자 (User - 모바일 웹)
// ══════════════════════════════════════════════
Route::prefix('user')->name('user.')->group(function () {
    Route::get('login',  [UserAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [UserAuthController::class, 'login'])->name('login.submit');
    Route::post('logout',[UserAuthController::class, 'logout'])->name('logout');

    Route::middleware('employee.auth')->group(function () {
        Route::get('idcard',          [IdCardController::class, 'show'])->name('idcard');
        Route::get('idcard/download', [IdCardController::class, 'download'])->name('idcard.download');
        Route::get('idcard/qr-data',  [IdCardController::class, 'qrData'])->name('idcard.qr-data');
        Route::get('guide',           [IdCardController::class, 'guide'])->name('guide');
    });
});

// ══════════════════════════════════════════════
// QR 코드 검증 (공개)
// ══════════════════════════════════════════════
Route::get('verify/{token}', [QrVerifyController::class, 'verify'])->name('qr.verify');

// API
Route::prefix('api')->group(function () {
    Route::get('verify/{token}', [QrVerifyController::class, 'apiVerify'])->name('api.qr.verify');
});
