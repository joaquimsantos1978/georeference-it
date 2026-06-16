<?php

use App\Http\Controllers\Api\OccurrenceController;
use App\Http\Controllers\Api\DatasetApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('occurrences', [OccurrenceController::class, 'index']);
    Route::get('occurrences/{gbif_key}', [OccurrenceController::class, 'show']);
    Route::get('datasets', [DatasetApiController::class, 'index'])->name('api.datasets');

    Route::get('tiles/{z}/{x}/{y}', function (int $z, int $x, int $y) {
        if ($z < 0 || $z > 19 || $x < 0 || $y < 0) {
            return response('', 400);
        }

        $tile = Http::withHeaders(['User-Agent' => 'georeference.it/1.0 (+https://georeference.it)'])
            ->timeout(8)
            ->get("https://tile.openstreetmap.org/{$z}/{$x}/{$y}.png");

        if (!$tile->ok()) {
            return response('', 502);
        }

        return response($tile->body(), 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    });
});
