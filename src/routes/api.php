<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AppIntroController;

Route::get('/app-intros', [AppIntroController::class, 'index']);

Route::post('/register', [AuthController::class, 'register']);

Route::post('/dashboard/login', [AuthController::class, 'loginDashboard']);
Route::post('/app/login', [AuthController::class, 'loginApp']);

Route::post('/refresh', [AuthController::class, 'refresh']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::middleware(['jwt'])->group(function () {

    Route::get('/me', [AuthController::class, 'me']);


    Route::get('/people', [PersonController::class, 'index']);
    Route::get('/people/{id}', [PersonController::class, 'show']);
    Route::post('/people', [PersonController::class, 'store']);
    Route::put('/people/{id}', [PersonController::class, 'update']);
    Route::delete('/people/{id}', [PersonController::class, 'destroy']);

    Route::middleware(['role:admin,manager,cashier'])->group(function () {

        Route::post('/app-intros', [AppIntroController::class, 'store']);
        Route::put('/app-intros/{id}', [AppIntroController::class, 'update']);

        Route::get('/app-intros', [AppIntroController::class, 'index']);
        Route::get('/app-intros/{id}', [AppIntroController::class, 'show']);
    });

    Route::middleware('role:admin,manager')->group(function () {

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
        });

        Route::prefix('people')->group(function () {
            Route::get('/', [PersonController::class, 'index']);
            Route::post('/', [PersonController::class, 'store']);
            Route::put('/{id}', [PersonController::class, 'update']);
        });

        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::put('/{id}', [CategoryController::class, 'update']);
        });

        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::put('/{id}', [ProductController::class, 'update']);
        });
    });

    Route::middleware('role:admin')->group(function () {

        Route::delete('/people/{id}', [PersonController::class, 'destroy']);
        Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
        Route::delete('/app-intros/{id}', [AppIntroController::class, 'destroy']);
    });

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);

    Route::prefix('orders')->group(function () {

        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);

        Route::post('/{orderId}/items', [OrderItemController::class, 'store']);
        Route::delete('/{orderId}/items/{itemId}', [OrderItemController::class, 'destroy']);

        Route::middleware('role:admin,manager')->group(function () {
            Route::delete('/{id}', [OrderController::class, 'destroy']);
        });
    });

    Route::prefix('payments')->group(function () {
        Route::post('/checkout/{id}', [PaymentController::class, 'checkout']);
        Route::post('/verify', [PaymentController::class, 'verifyTransaction']);
    });

});





















// <?php

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\AuthController;
// use App\Http\Controllers\UserController;
// use App\Http\Controllers\PersonController;
// use App\Http\Controllers\CategoryController;
// use App\Http\Controllers\ProductController;
// use App\Http\Controllers\OrderController;
// use App\Http\Controllers\OrderItemController;
// use App\Http\Controllers\PaymentController;
// use App\Http\Controllers\AppIntroController;

// Route::prefix('auth')->group(function () {
//     Route::post('/register', [AuthController::class, 'register']);
//     Route::post('/login/app', [AuthController::class, 'loginApp']);
//     Route::post('/login/dashboard', [AuthController::class, 'loginDashboard']);
//     Route::post('/refresh', [AuthController::class, 'refresh']);
//     Route::post('/logout', [AuthController::class, 'logout']);
// });

// Route::get('/app-intros', [AppIntroController::class, 'index']);
// Route::get('/categories', [CategoryController::class, 'index']);
// Route::get('/categories/{id}', [CategoryController::class, 'show']);
// Route::get('/products', [ProductController::class, 'index']);
// Route::get('/products/{id}', [ProductController::class, 'show']);

// Route::middleware(['jwt'])->group(function () {

//     Route::get('/me', [AuthController::class, 'me']);

//     Route::apiResource('people', PersonController::class);

//     Route::apiResource('orders', OrderController::class);
//     Route::post('orders/{order}/items', [OrderItemController::class, 'store']);
//     Route::delete('orders/{order}/items/{item}', [OrderItemController::class, 'destroy']);

//     Route::prefix('payments')->group(function () {
//         Route::post('/checkout/{id}', [PaymentController::class, 'checkout']);
//         Route::post('/verify', [PaymentController::class, 'verifyTransaction']);
//     });

//     Route::middleware('role:admin,manager')->group(function () {

//         Route::apiResource('users', UserController::class)
//             ->only(['index', 'store', 'show', 'update']);

//         Route::apiResource('categories', CategoryController::class)
//             ->only(['store', 'update']);

//         Route::apiResource('products', ProductController::class)
//             ->only(['store', 'update']);

//         Route::apiResource('app-intros', AppIntroController::class)
//             ->only(['index', 'show', 'store', 'update']);
//     });

//     Route::middleware('role:admin')->group(function () {

//         Route::delete('people/{id}', [PersonController::class, 'destroy']);
//         Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
//         Route::delete('products/{id}', [ProductController::class, 'destroy']);
//         Route::delete('orders/{id}', [OrderController::class, 'destroy']);
//         Route::delete('app-intros/{id}', [AppIntroController::class, 'destroy']);
//     });
// });