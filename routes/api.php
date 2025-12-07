<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// AI API routes for external integrations (WhatsApp, etc.)
Route::prefix('ai')->group(function () {
    Route::post('/chat', [App\Http\Controllers\Api\AIChatController::class, 'chat']);
    Route::post('/lookup-user', [App\Http\Controllers\Api\AIChatController::class, 'lookupUser']);
    Route::get('/health', [App\Http\Controllers\Api\AIChatController::class, 'health']);
    Route::get('/models', [App\Http\Controllers\Api\AIChatController::class, 'getModels']);
    Route::get('/financial-summary', [App\Http\Controllers\Api\AIChatController::class, 'getFinancialSummary']);
    Route::get('/history', [App\Http\Controllers\Api\AIChatController::class, 'getHistory']);
    Route::delete('/history', [App\Http\Controllers\Api\AIChatController::class, 'clearHistory']);
    Route::post('/whatsapp/webhook', [App\Http\Controllers\Api\AIChatController::class, 'whatsappWebhook']);
});

// Messaging webhook routes (WhatsApp, Telegram, etc. via n8n)
Route::prefix('messaging')->group(function () {
    Route::post('/webhook', [App\Http\Controllers\MessagingWebhookController::class, 'handle']);
    Route::get('/webhook', [App\Http\Controllers\MessagingWebhookController::class, 'verify']);
    Route::get('/status', [App\Http\Controllers\MessagingWebhookController::class, 'status']);
});

// Messaging webhook routes (n8n, Evolution API, WhatsApp)
Route::prefix('messaging')->group(function () {
    Route::post('/webhook', [App\Http\Controllers\MessagingWebhookController::class, 'handle']);
    Route::get('/webhook', [App\Http\Controllers\MessagingWebhookController::class, 'verify']);
    Route::get('/status', [App\Http\Controllers\MessagingWebhookController::class, 'status']);
});
