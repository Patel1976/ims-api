<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\StoreController;
use App\Http\Controllers\Api\ProductController;

Route::prefix('admin')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware([JwtMiddleware::class])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/users', [UserController::class, 'getAllUsers']);
    Route::post('/create-user', [UserController::class, 'createUser']);
    Route::put('/update-user/{id}', [UserController::class, 'updateUser']);
    Route::delete('/delete-user/{id}', [UserController::class, 'deleteUser']);
    // Customer Routes
    Route::get('/customers', [CustomerController::class, 'getAllCustomer']);
    Route::get('/view-customers/{id}', [CustomerController::class, 'viewCustomer']);
    Route::post('/create-customer', [CustomerController::class, 'createCustomer']);
    Route::put('/update-customer/{id}', [CustomerController::class, 'updateCustomer']);
    Route::delete('/delete-customer/{id}', [CustomerController::class, 'deleteCustomer']);
    // Supplier Routes
    Route::get('/suppliers', [SupplierController::class, 'getAllSuppliers']);
    Route::get('/view-suppliers/{id}', [SupplierController::class, 'viewSupplier']);
    Route::post('/create-supplier', [SupplierController::class, 'createSupplier']);
    Route::put('/update-supplier/{id}', [SupplierController::class, 'updateSupplier']);
    Route::delete('/delete-supplier/{id}', [SupplierController::class, 'deleteSupplier']);
    // Store Routes
    Route::get('/stores', [StoreController::class, 'getAllStore']);
    Route::get('/view-stores/{id}', [StoreController::class, 'viewStore']);
    Route::post('/create-store', [StoreController::class, 'createStore']);
    Route::put('/update-store/{id}', [StoreController::class, 'updateStore']);
    Route::delete('/delete-store/{id}', [StoreController::class, 'deleteStore']);
    // Product Routes
    Route::get('/products', [ProductController::class, 'getAllProducts']);
    Route::get('/view-products/{id}', [ProductController::class, 'viewProduct']);
    Route::post('/create-product', [ProductController::class, 'createProduct']);
    Route::post('/update-product/{id}', [ProductController::class, 'updateProduct']);
    Route::delete('/delete-product/{id}', [ProductController::class, 'deleteProduct']);
});
