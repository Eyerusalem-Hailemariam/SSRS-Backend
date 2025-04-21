<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\AdminController;
use App\Http\Controllers\Payment\ChapaController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\TableController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\IngredientController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\MenuTagController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ImageController;

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






//order routes 

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::patch('/{id}/notify-arrival', [OrderController::class, 'notifyArrival']);
        Route::put('/{id}', [OrderController::class, 'update']);
        Route::patch('/{id}/status', [OrderController::class, 'changeStatus']);
        Route::get('/user', [OrderController::class, 'getUserOrders']);
    });

//MenuItem routes

Route::prefix('menuitems')->group(function () {
    Route::get('/', [MenuItemController::class, 'index']); // Get all menu items
    Route::post('/', [MenuItemController::class, 'store']); // Create a menu item
    Route::get('/{id}', [MenuItemController::class, 'show']); // Get a single menu item
    Route::put('/{id}', [MenuItemController::class, 'update']); // Update a menu item
    Route::delete('/{id}', [MenuItemController::class, 'destroy']); // Delete a menu item
});

//Table routes

Route::prefix('tables')->group(function () {
    Route::get('/', [TableController::class, 'index']); // Get all tables
    Route::post('/range', [TableController::class, 'storeByRange']); // Create tables in a range
    Route::post('/', [TableController::class, 'store']); // Create a table
    Route::get('/{id}', [TableController::class, 'show']); // Get a single table
    Route::put('/{id}', [TableController::class, 'update']); // Update a table
    Route::delete('/batch', [TableController::class, 'destroyBatchByRange']);
    Route::delete('/all', [TableController::class, 'destroyAll']);
    Route::delete('/{table_number}', [TableController::class, 'destroy']); // Delete a table by table_number
    
});
//OrderItem routes

Route::prefix('orderitems')->group(function () {
    Route::get('/', [OrderItemController::class, 'index']); // Get all order items
    Route::post('/{orderId}', [OrderItemController::class, 'store']); // Add an item to an order
    Route::get('/{id}', [OrderItemController::class, 'show']); // Get a single order item
    Route::put('/{id}', [OrderItemController::class, 'update']); // Update an order item
    Route::delete('/{id}', [OrderItemController::class, 'destroy']); // Delete an order item
});

//ingredient routes

Route::prefix('ingredients')->group(function () {
    Route::get('/', [IngredientController::class, 'index']); // Get all ingredients
    Route::post('/', [IngredientController::class, 'store']); // Create new ingredient
    Route::get('/{id}', [IngredientController::class, 'show']); // Get a single ingredient
    Route::put('/{id}', [IngredientController::class, 'update']); // Update ingredient
    Route::delete('/{id}', [IngredientController::class, 'destroy']); // Delete ingredient
});

//tag routes

Route::prefix('tags')->group(function () {
    Route::get('/', [TagController::class, 'index']); // Get all tags
    Route::post('/', [TagController::class, 'store']); // Create a new tag
    Route::get('/{tag}', [TagController::class, 'show']); // Get a single tag
    Route::put('/{tag}', [TagController::class, 'update']); // Update a tag
    Route::delete('/{tag}', [TagController::class, 'destroy']); // Delete a tag
});

//menuTag routes

Route::prefix('menu-tags')->group(function () {
    Route::get('/', [MenuTagController::class, 'index']); // Get all menu tags
    Route::post('/', [MenuTagController::class, 'store']); // Create a new menu tag by attaching a tag
    Route::delete('/{menuId}/{tagId}', [MenuTagController::class, 'destroy']); // Delete a menu tag by detaching a tag
});

//category routes

Route::prefix('categories')->group(function () {
    Route::get('/', [CategoryController::class, 'index']); // Get all categories
    Route::post('/', [CategoryController::class, 'store']); // Create a new category
    Route::get('/{category}', [CategoryController::class, 'show']); // Get a single category
    Route::put('/{category}', [CategoryController::class, 'update']); // Update a category
    Route::delete('/{category}', [CategoryController::class, 'destroy']); // Delete a category
});


//image routes

Route::prefix('images')->group(function () {
    Route::get('/', [ImageController::class, 'index']); // Get all images
    Route::post('/', [ImageController::class, 'store']); // Create a new image
    Route::get('/{id}', [ImageController::class, 'show']); // Get a specific image
    Route::put('/{id}', [ImageController::class, 'update']); // Update a specific image
    Route::delete('/{id}', [ImageController::class, 'destroy']); // Delete a specific image
});