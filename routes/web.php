<?php

use App\Http\Controllers\Web\ResetPasswordController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return view('welcome');
});


// Password Reset Routes
Route::prefix('password')->name('password.')->group(function () {
    Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
        ->name('reset');

    Route::post('/reset', [ResetPasswordController::class, 'reset'])
        ->name('update');
});
