<?php

use App\Http\Controllers\API\AI\SpeechController;
use App\Http\Controllers\API\AppVersionController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\AuthPasswordController;
use App\Http\Controllers\API\OnboardingController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| App Routes (v1/app/...)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/app')->name('app.')->group(function () {

    Route::prefix('auth')->name('auth.')->group(function () {

        Route::post('register', [AuthController::class, 'register'])->name('register')->middleware('throttle:10,1');
        Route::post('login', [AuthController::class, 'login'])->name('login')->middleware('throttle:10,1');

        Route::get('{provider}/redirect', [AuthController::class, 'socialRedirect'])
            ->whereIn('provider', ['google', 'facebook'])
            ->name('social.redirect')->middleware('throttle:20,1');

        Route::get('{provider}/callback', [AuthController::class, 'socialCallback'])
            ->whereIn('provider', ['google', 'facebook'])
            ->name('social.callback')->middleware('throttle:20,1');

        Route::post('forgot-password', [AuthPasswordController::class, 'forgotPassword'])->name('forgot')->middleware('throttle:5,1');
        Route::post('reset-password', [AuthPasswordController::class, 'resetPassword'])->name('reset')->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('change-password', [AuthPasswordController::class, 'changePassword'])->name('change')->middleware('throttle:10,1');
            Route::post('validate-token', [AuthController::class, 'validateToken'])->name('validate-token');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        });
    });

    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('screens', [OnboardingController::class, 'getScreens'])
            ->name('screens')->middleware('throttle:20,1');
    });

    Route::prefix('version')->name('version.')->group(function () {
        Route::post('check', [AppVersionController::class, 'checkVersion'])
            ->name('check')->middleware('throttle:30,1');
        Route::get('latest', [AppVersionController::class, 'getLatestVersion'])
            ->name('latest')->middleware('throttle:30,1');
    });

    Route::prefix('ai')->middleware(['auth:sanctum'])->group(function () {
        Route::post('/speech/transcribe', [SpeechController::class, 'transcribe'])
            ->middleware('throttle:10,1');
        Route::post('/speech/transcribe/url', [SpeechController::class, 'transcribeUrl'])->middleware('throttle:10,1');

    });

});

/*
|--------------------------------------------------------------------------
| Admin Routes (v1/admin/...)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/admin')->name('admin.')->middleware('auth:sanctum')->group(function () {

    // -------- Onboarding (Admin) --------
    Route::prefix('onboarding-screens')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::post('/', [OnboardingController::class, 'store'])->name('store');
        Route::get('{onboardingScreen}', [OnboardingController::class, 'show'])->name('show');
        Route::put('{onboardingScreen}', [OnboardingController::class, 'update'])->name('update');
        Route::delete('{onboardingScreen}', [OnboardingController::class, 'destroy'])->name('destroy');

        Route::post('{onboardingScreen}/toggle-status', [OnboardingController::class, 'toggleStatus'])
            ->name('toggle-status');

        Route::post('reorder', [OnboardingController::class, 'reorder'])->name('reorder');
    });

    // -------- App Version Management (Admin) --------
    Route::prefix('app-versions')->name('app-versions.')->group(function () {
        Route::get('/', [AppVersionController::class, 'adminIndex'])->name('index');
        Route::post('/', [AppVersionController::class, 'adminStore'])->name('store');
        Route::get('{appVersion}', [AppVersionController::class, 'adminShow'])->name('show');
        Route::put('{appVersion}', [AppVersionController::class, 'adminUpdate'])->name('update');
        Route::delete('{appVersion}', [AppVersionController::class, 'adminDestroy'])->name('destroy');

        Route::post('{appVersion}/toggle-status', [AppVersionController::class, 'adminToggleStatus'])
            ->name('toggle-status');
    });

});
