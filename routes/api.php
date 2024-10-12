<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JurnalController;

Route::group(['prefix' => 'user'], function () {
    Route::post('/login', [UserController::class, 'login']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/update-image', [UserController::class, 'updateimage'])->middleware('auth:sanctum');
    Route::post('/change-password', [UserController::class, 'changepassword'])->middleware('auth:sanctum');
});

Route::group(['prefix' => 'admin'], function () {
    Route::get('/list-user', [UserController::class, 'index']);
    Route::get('/viewjurnal', [JurnalController::class, 'viewJurnalByTimeRange'])->middleware('auth:sanctum');
    Route::put('/update-user/{id}', [UserController::class, 'edit'])->middleware('auth:sanctum');
    Route::post('/register-user', [UserController::class, 'register']);
    Route::delete('/delete-user/{id}', [UserController::class, 'delete'])->middleware('auth:sanctum');
    Route::post('/register', [AdminController::class, 'Adminregister']);
    Route::post('/login', [AdminController::class, 'Adminlogin']);
    Route::get('/details', [AdminController::class, 'Admindetails'])->middleware('auth:sanctum');
    Route::post('/logout', [AdminController::class, 'Adminlogout'])->middleware('auth:sanctum');
    Route::put('/update-image', [AdminController::class, 'Adminupdateimage'])->middleware('auth:sanctum');
    Route::post('/change-password', [AdminController::class, 'Adminchangepassword'])->middleware('auth:sanctum');
});

Route::group(['prefix' => 'jurnal'], function () {
    Route::get('/', [JurnalController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/{id}', [JurnalController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/input', [JurnalController::class, 'input'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [JurnalController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/delete/{id}', [JurnalController::class, 'delete'])->middleware('auth:sanctum');
});