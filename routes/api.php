<?php 

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VendorController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('vendors')->group(function () {
        Route::get('/all', [VendorController::class, 'all'])->middleware('isAdmin');
        Route::get('/', [VendorController::class, 'index']);
        Route::post('/', [VendorController::class, 'store']);
        Route::get('/{id}', [VendorController::class, 'show']);
        Route::put('/{id}', [VendorController::class, 'update']);
        Route::delete('/{id}', [VendorController::class, 'destroy']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::post('/import-csv', [ProductController::class, 'importCsv']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

});
