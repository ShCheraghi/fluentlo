<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\{
    AppVersionController,
    AuthController,
    AuthPasswordController,
    ConversationController,
    LevelController,
    OnboardingController,
    UserAssessmentController,
    NotificationController,
    UserLevelController,
    UnitController
};
use App\Http\Controllers\Dev\BroadcastTestController;


/*
|--------------------------------------------------------------------------
| API (App) Routes  → /v1/app
|--------------------------------------------------------------------------
*/
Route::prefix('v1/app')->name('app.')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Auth (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('auth.')->group(function () {

        // Public
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login');

        Route::get('{provider}/redirect', [AuthController::class, 'socialRedirect'])
            ->whereIn('provider', ['google', 'facebook'])
            ->middleware('throttle:20,1')
            ->name('social.redirect');

        Route::get('{provider}/callback', [AuthController::class, 'socialCallback'])
            ->whereIn('provider', ['google', 'facebook'])
            ->middleware('throttle:20,1')
            ->name('social.callback');

        Route::post('forgot-password', [AuthPasswordController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot');
        Route::post('reset-password', [AuthPasswordController::class, 'resetPassword'])->middleware('throttle:5,1')->name('reset');

        // Private
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('change-password', [AuthPasswordController::class, 'changePassword'])->middleware('throttle:10,1')->name('change');
            Route::post('validate-token', [AuthController::class, 'validateToken'])->name('validate-token');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::post('logout-all', [AuthController::class, 'logoutAll'])->name('logout-all');
        });

    });


    /*
    |--------------------------------------------------------------------------
    | Onboarding (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('screens', [OnboardingController::class, 'getScreens'])
            ->middleware('throttle:20,1')
            ->name('screens');
    });


    /*
    |--------------------------------------------------------------------------
    | App Version (Public)
    |--------------------------------------------------------------------------
    */
    Route::prefix('version')->name('version.')->group(function () {
        Route::post('check', [AppVersionController::class, 'checkVersion'])->middleware('throttle:30,1')->name('check');
        Route::get('latest', [AppVersionController::class, 'getLatestVersion'])->middleware('throttle:30,1')->name('latest');
    });


    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Requires Login)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Assessment
        |--------------------------------------------------------------------------
        */
        Route::prefix('assessment')->name('assessment.')->group(function () {
            Route::post('user', [UserAssessmentController::class, 'store'])->middleware('throttle:10,1')->name('store');
            Route::get('user', [UserAssessmentController::class, 'show'])->middleware('throttle:30,1')->name('show');
            Route::delete('user', [UserAssessmentController::class, 'destroy'])->middleware('throttle:5,1')->name('destroy');
        });


        /*
        |--------------------------------------------------------------------------
        | Conversation
        |--------------------------------------------------------------------------
        */
        Route::prefix('conversation')->name('conversation.')->group(function () {
            Route::post('transcribe', [ConversationController::class, 'transcribe'])->middleware('throttle:10,1')->name('transcribe');
            Route::post('start', [ConversationController::class, 'start'])->middleware('throttle:5,1')->name('start');
            Route::post('message', [ConversationController::class, 'message'])->middleware('throttle:30,1')->name('message');
        });


        /*
        |--------------------------------------------------------------------------
        | Notifications
        |--------------------------------------------------------------------------
        */
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('{id}/read', [NotificationController::class, 'markAsRead'])->name('read');
            Route::post('read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
            Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
        });


        /*
        |--------------------------------------------------------------------------
        | Levels
        |--------------------------------------------------------------------------
        */
        Route::apiResource('levels', LevelController::class)->except('create', 'edit');
        Route::post('levels/reorder', [LevelController::class, 'reorder'])->name('levels.reorder');


        /*
        |--------------------------------------------------------------------------
        | User Level
        |--------------------------------------------------------------------------
        */
        Route::prefix('user-levels')->name('user-levels.')->group(function () {
            Route::get('current', [UserLevelController::class, 'current'])->name('current');
            Route::post('start/{level}', [UserLevelController::class, 'start'])->name('start');
            Route::post('next', [UserLevelController::class, 'nextLevel'])->name('next');
        });


        /*
        |--------------------------------------------------------------------------
        | Units
        |--------------------------------------------------------------------------
        */
        Route::prefix('units')->name('units.')->group(function () {

            // GET /levels/{level}/units
            Route::get('/levels/{level}/units', [UnitController::class, 'index'])->name('by-level');

            Route::post('/', [UnitController::class, 'store'])->name('store');
            Route::get('{unit}', [UnitController::class, 'show'])->name('show');
            Route::put('{unit}', [UnitController::class, 'update'])->name('update');
            Route::delete('{unit}', [UnitController::class, 'destroy'])->name('destroy');
        });

    });

});



/*
|--------------------------------------------------------------------------
| Dev Routes
|--------------------------------------------------------------------------
*/
if (app()->environment('local')) {
    Route::post('/v1/dev/notifications/test', [BroadcastTestController::class, 'send'])
        ->middleware(['auth:sanctum', 'throttle:5,1'])
        ->name('dev.notifications.test');
}



/*
|--------------------------------------------------------------------------
| Admin Routes (v1/admin)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/admin')->name('admin.')->middleware('auth:sanctum')->group(function () {

    // Onboarding
    Route::apiResource('onboarding-screens', OnboardingController::class);
    Route::post('onboarding-screens/{onboardingScreen}/toggle-status', [OnboardingController::class, 'toggleStatus'])
        ->name('onboarding.toggle-status');

    // App Versions
    Route::apiResource('app-versions', AppVersionController::class);
    Route::post('app-versions/{appVersion}/toggle-status', [AppVersionController::class, 'adminToggleStatus'])
        ->name('app-versions.toggle-status');
});
