<?php

use App\Http\Controllers\Api\OccurrenceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('occurrences', [OccurrenceController::class, 'index']);
    Route::get('occurrences/{gbif_key}', [OccurrenceController::class, 'show']);
});
