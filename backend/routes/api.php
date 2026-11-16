<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\ChannelController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'TelePinoy API is running.',
    ]);
});

Route::get('/channels', [ChannelController::class, 'index']);
Route::get('/channels/{slug}', [ChannelController::class, 'show']);
Route::post('/channels/{slug}/views', [ChannelController::class, 'recordView'])->middleware('throttle:30,1');

Route::post('/admin/login', [AdminAuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/user', [AdminAuthController::class, 'user']);
    Route::post('/logout', [AdminAuthController::class, 'logout']);
    Route::get('/dashboard', [ChannelController::class, 'adminDashboard']);
    Route::get('/channels', [ChannelController::class, 'adminIndex']);
    Route::post('/channels', [ChannelController::class, 'store']);
    Route::put('/channels/{slug}', [ChannelController::class, 'update']);
    Route::delete('/channels/{slug}', [ChannelController::class, 'destroy']);
    Route::post('/channels/import-iptv-org', [ChannelController::class, 'importIptvOrg']);
    Route::post('/channels/{slug}/test-success', [ChannelController::class, 'markStreamTested']);
    Route::put('/channels/{slug}/playability', [ChannelController::class, 'setPlayability']);
});
