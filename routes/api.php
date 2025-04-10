<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AdminController;
use App\Http\Controllers\Payment\ChapaController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Models\Staff;
use App\Http\Controllers\Shift\ShiftController;
use App\Http\Controllers\Shift\OvertimeController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\Auth\StaffAuthController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/verify-otp', [AuthController::class, 'VerifyOtp']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/register/admin', [AuthController::class, 'registerAdmin']);
Route::post('/staff/login', [StaffAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [AdminController::class, 'registerStaff']);
        Route::put('/admin/updateStaff', [AdminController::class, 'updateStaff']);
        Route::Delete('/admin/staff', [AdminController::class, 'deleteStaff']);
    });
    Route::get('/user/profile', [AuthController::class, 'getUserProfile']);
    Route::post('/staff/change-password', [StaffAuthController::class, 'changePassword']);
    Route::post('/user/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::post('/forgot-password', [StaffAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [StaffAuthController::class, 'resetPassword']);
    Route::put('/staff/update', [StaffAuthController::class, 'updateAccount']);  
});
Route::get('/api/documentation', function () {
    return view('l5-swagger::index');
});
// Payment routes
Route::get('callback/{reference}', [ChapaController::class, 'callback'])->name('callback.api');
Route::post('/payment/chapa/initialize', [ChapaController::class, 'initializePayment']);

// //attendance routes
// Route::middleware('auth:sanctum')->group(function () {
//     Route::post('/scan', [AttendanceController::class, 'scan']);
// });

//Shift Route
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/shift/set-global-period', [ShiftController::class, 'setGlobalShiftPeriod']);
    Route::get('/shifts', [ShiftController::class, 'index']); 
    Route::post('/shifts', [ShiftController::class, 'store']); 
    Route::put('/shifts/{id}', [ShiftController::class, 'update']); 
    Route::delete('/shifts/{id}', [ShiftController::class, 'destroy']);
    Route::post('/overtime', [OvertimeController::class, 'store']); 
});
