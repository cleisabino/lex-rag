<?php

use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\QueryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function() {
    Route::apiResource('documents', DocumentController::class)->only(['index', 'store', 'show']);
    Route::post('query', [QueryController::class, 'query']);
    Route::get('health', fn() => response()->json(['status' => 'ok', 'timestamp' => now()]));
});