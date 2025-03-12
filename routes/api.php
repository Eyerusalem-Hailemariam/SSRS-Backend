<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AdminController;
use App\Http\Controllers\Payment\ChapaController;
use App\Http\Controllers\Auth\ProfileController;
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
Route::post('/logout', [AuthController::class, 'logout']);
Route::post('/register/admin', [AuthController::class, 'registerAdmin']);
Route::middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin')->group(function () {
        Route::post('/admin/users', [AdminController::class, 'registerStaff']);
    });
    Route::get('/user/profile', [AuthController::class, 'getUserProfile']);
    Route::post('/user/profile/change-password', [ProfileController::class, 'changePassword']);
});

Route::get('callback/{reference}', [ChapaController::class, 'callback'])->name('callback.api');
// Payment routes
Route::post('/payment/chapa/initialize', [ChapaController::class, 'initializePayment']);




