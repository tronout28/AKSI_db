<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;



Route::group(['prefix' => 'user'], function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/update-image', [UserController::class, 'updateimage'])->middleware('auth:sanctum');
    Route::post('/change-password', [UserController::class, 'changepassword'])->middleware('auth:sanctum');

});

Route::group(['prefix' => 'admin'], function () {
    Route::post('/register-user', [UserController::class, 'register']);
});