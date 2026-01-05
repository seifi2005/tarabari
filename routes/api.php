<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReceptorAuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReceptorController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReceptorWorkflowController;
use App\Http\Controllers\Api\ShipmentController;
use App\Http\Controllers\Api\ProviderController;
use App\Http\Controllers\Api\ShipmentProviderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
 
 

// ========== Receptor Authentication (JWT) ==========
Route::post('/get_token', [ReceptorAuthController::class, 'getToken']);

Route::middleware('jwt.auth')->group(function () {
    Route::get('/receptor/me', [ReceptorAuthController::class, 'me']);
    Route::post('/receptor/refresh', [ReceptorAuthController::class, 'refresh']);
    // سایر route های receptor اینجا
});

// ========== User Authentication (Sanctum) ==========
Route::post('/auth/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/auth/login/otp', [AuthController::class, 'loginWithOtp']);
Route::post('/auth/login/password', [AuthController::class, 'loginWithPassword']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    // User Management (super_admin & operator only)
    Route::apiResource('users', UserController::class);

    // Receptor Management (super_admin & operator only)
    Route::apiResource('receptors', ReceptorController::class);
    Route::get('/receptors/{id}/providers', [ReceptorController::class, 'getProviders']);
    Route::post('/receptors/{id}/providers', [ReceptorController::class, 'attachProviders']);

    // Order Management (super_admin & operator only)
    Route::get('/orders/receptors', [OrderController::class, 'getReceptorsForOrders']);
    Route::post('/orders/check/{receptorId}', [OrderController::class, 'checkOrders']);
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::post('/orders/search', [OrderController::class, 'search']);

    // Workflow Management (super_admin & operator only)
    Route::get('/receptors/workflow/available-actions', [ReceptorWorkflowController::class, 'getAvailableActions']);
    Route::get('/receptors/{receptorId}/workflow', [ReceptorWorkflowController::class, 'show']);
    Route::post('/receptors/{receptorId}/workflow', [ReceptorWorkflowController::class, 'store']);
    Route::put('/receptors/{receptorId}/workflow', [ReceptorWorkflowController::class, 'update']);
    Route::delete('/receptors/{receptorId}/workflow', [ReceptorWorkflowController::class, 'destroy']);

    // Shipment Management (super_admin & operator only)
    Route::get('/shipments', [ShipmentController::class, 'index']);
    Route::get('/shipments/{id}', [ShipmentController::class, 'show']);

    // Provider Management (super_admin & operator only)
    Route::apiResource('providers', ProviderController::class);

    // Shipment Provider Management (super_admin & operator only)
    Route::post('/shipments/{id}/send-to-provider', [ShipmentProviderController::class, 'send']);
    Route::get('/shipments/{id}/track-provider', [ShipmentProviderController::class, 'track']);
    Route::post('/shipments/{id}/cancel-provider', [ShipmentProviderController::class, 'cancel']);
});
