<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Controllers\AuthController;

Route::prefix('admin')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware([JwtMiddleware::class])->prefix('admin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});
