<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SetLocaleController;
use Illuminate\Http\Request;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('devices', \App\Http\Controllers\DeviceController::class);
    Route::resource('operators', \App\Http\Controllers\OperatorController::class);
    Route::resource('sensors', \App\Http\Controllers\SensorController::class);
    Route::post('sensors/bulk-delete', [\App\Http\Controllers\SensorController::class, 'bulkDelete'])->name('sensors.bulkDelete');

    // コード重複チェック用（Ajax）
    Route::post('sensors/checkCode', [\App\Http\Controllers\SensorController::class, 'checkCode'])
        ->name('sensors.checkCode');    
        // 他の認証が必要なルートもここに追加
    // コード重複チェック用（Ajax）
    Route::post('sensors/checkSerialNumber', [\App\Http\Controllers\SensorController::class, 'checkSerialNumber'])
        ->name('sensors.checkSerialNumber');    
        // 他の認証が必要なルートもここに追加
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'verified'])->group(function () {
    Route::resource('companies', \App\Http\Controllers\Admin\CompanyController::class);
});

Route::get('/exam-application', function () {
    return Inertia::render('Exam/ExamApplication');
})->middleware(['auth', 'verified'])->name('exam.application');

Route::prefix('exam')->name('exam.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('applications', [\App\Http\Controllers\ExamApplicationController::class, 'index'])->name('applications.index');
    Route::get('applications/{id}', [\App\Http\Controllers\ExamApplicationController::class, 'show'])->name('applications.show');
});

Route::post('/locale', function (Request $request) {
    $locale = $request->input('locale', 'en');
    session(['locale' => $locale]);
    app()->setLocale($locale);
    return response()->json(['status' => 'ok']);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});
