<?php

use App\Http\Controllers\Api\OccurrenceController;
use App\Http\Controllers\Api\DatasetApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('occurrences', [OccurrenceController::class, 'index']);
    Route::get('occurrences/{gbif_key}', [OccurrenceController::class, 'show']);
    Route::get('datasets', [DatasetApiController::class, 'index'])->name('api.datasets');
});
