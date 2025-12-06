<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AI API routes for external integrations (WhatsApp, etc.)
Route::prefix('ai')->group(function () {
    Route::post('/chat', [App\Http\Controllers\Api\AIChatController::class, 'chat']);
    Route::get('/health', [App\Http\Controllers\Api\AIChatController::class, 'health']);
    Route::get('/models', [App\Http\Controllers\Api\AIChatController::class, 'getModels']);
    Route::get('/financial-summary', [App\Http\Controllers\Api\AIChatController::class, 'getFinancialSummary']);
    Route::get('/history', [App\Http\Controllers\Api\AIChatController::class, 'getHistory']);
    Route::delete('/history', [App\Http\Controllers\Api\AIChatController::class, 'clearHistory']);
    Route::post('/whatsapp/webhook', [App\Http\Controllers\Api\AIChatController::class, 'whatsappWebhook']);
});
