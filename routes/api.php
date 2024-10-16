<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\JurnalController;
use App\Http\Controllers\SicknessController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HomewardController;
use App\Http\Controllers\TugasController;

Route::group(['prefix' => 'user'], function () {
    Route::get('/list-sakit', [SicknessController::class, 'indexUser'])->middleware('auth:sanctum');
    Route::get('/detail-sakit/{id}', [SicknessController::class, 'detail'])->middleware('auth:sanctum');
    Route::post('/input-sakit', [SicknessController::class, 'input'])->middleware('auth:sanctum');


    Route::get('/list-izin', [PermissionController::class, 'indexUser'])->middleware('auth:sanctum');
    Route::get('/detail-izin/{id}', [PermissionController::class, 'detail'])->middleware('auth:sanctum');
    Route::post('/input-izin', [PermissionController::class, 'input'])->middleware('auth:sanctum');

    Route::post('/login', [UserController::class, 'login']);
    Route::get('/details', [UserController::class, 'details'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/update-image', [UserController::class, 'updateimage'])->middleware('auth:sanctum');
    Route::post('/change-password', [UserController::class, 'changepassword'])->middleware('auth:sanctum');
    Route::get('/history', [UserController::class, 'getMonthlyHistory'])->middleware('auth:sanctum');
});

Route::group(['prefix' => 'admin'], function () {
    Route::get('/list-user', [UserController::class, 'index']);
    Route::post('/register-user', [UserController::class, 'register']);
    Route::put('/update-user/{id}', [UserController::class, 'edit']);
    Route::delete('/delete-user/{id}', [UserController::class, 'delete']);

    Route::get('/list-sakit', [SicknessController::class, 'index']);
    Route::get('/detail-sakit/{id}', [SicknessController::class, 'detail']);
    Route::post('/sakit-allow/{id}', [SicknessController::class, 'updateAllowedSickness']);

    Route::get('/list-izin', [PermissionController::class, 'index']);
    Route::get('/detail-izin/{id}', [PermissionController::class, 'detail']);
    Route::post('/izin-allow/{id}', [PermissionController::class, 'updateAllowedPermission']);


    Route::get('/viewjurnal', [JurnalController::class, 'viewJurnalByTimeRange'])->middleware('auth:sanctum');
    Route::get('/getalljurnal', [JurnalController::class, 'getAllJurnals']);

    Route::get('/filter-attendances', [AttendanceController::class, 'filterAttendances']);
    Route::get('/chartattendance', [AttendanceController::class, 'getWeeklyAttendanceChart']);

    Route::get('/getweekly', [AdminController::class, 'getWeeklySummary']);

    Route::post('/register', [AdminController::class, 'Adminregister']);
    Route::post('/login', [AdminController::class, 'Adminlogin']);
    Route::get('/details', [AdminController::class, 'Admindetails'])->middleware('auth:sanctum');
    Route::post('/logout', [AdminController::class, 'Adminlogout'])->middleware('auth:sanctum');
    Route::put('/update-image', [AdminController::class, 'Adminupdateimage'])->middleware('auth:sanctum');
    Route::post('/change-password', [AdminController::class, 'Adminchangepassword'])->middleware('auth:sanctum');

    Route::get('/generate-qr', [AdminController::class, 'generateDailyQrCode']);
    Route::get('/generate-pulang', [AdminController::class, 'generateDailyQrCodeForCheckout']);
});

Route::group(['prefix' => 'jurnal'], function () {
    Route::get('/', [JurnalController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/{id}', [JurnalController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/input', [JurnalController::class, 'input'])->middleware('auth:sanctum');
    Route::put('/update/{id}', [JurnalController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/delete/{id}', [JurnalController::class, 'delete'])->middleware('auth:sanctum');
    Route::get('/jurnals', [JurnalController::class, 'getAllJournals'])->middleware('auth:sanctum');
});

Route::group(['prefix' => 'notifications', 'middleware' => ['auth:sanctum']], function () {
    Route::post('/send', [NotificationController::class, 'sendNotification']);
    Route::post('/send-to-all', [NotificationController::class, 'sendNotificationToAll']);
    Route::get('/all', [NotificationController::class, 'getNotifications']);
});

Route::group(['prefix' => 'attendance', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/', [AttendanceController::class, 'scanQrForCheckIn']);
    Route::get('/get-all', [AttendanceController::class, 'getAllAttendances']);
    Route::get('/get-user/{userId}', [AttendanceController::class, 'getAttendanceByUserId']);
});

Route::group(['prefix' => 'homeward', 'middleware' => 'auth:sanctum'], function () {
    Route::post('/store', [HomewardController::class, 'scanQrForCheckOut']);
    Route::get('/get-all', [HomewardController::class, 'getAllHomeward']);
    Route::get('/get-user/{userId}', [HomewardController::class, 'getHomewardByUserId']);
});

Route::group(['prefix' => 'tugas'], function () {
    Route::post('/store', [TugasController::class, 'storeTugas']);
    Route::put('/update-status/{tugasId}', [TugasController::class, 'updatestatusTugas']);
    Route::get('/get-all', [TugasController::class, 'getAllTugas']);
    Route::get('/get-user/{userId}', [TugasController::class, 'getUserTugas']);
    Route::put('/associate-jurnal/{tugasId}', [TugasController::class, 'associateJurnalToTugas']);
});