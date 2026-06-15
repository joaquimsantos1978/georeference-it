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

    Route::get('maptile', function (Request $request) {
        $lat = (float) $request->query('lat', 0);
        $lng = (float) $request->query('lng', 0);
        $zoom = 12;

        $x = (int) floor(($lng + 180) / 360 * (2 ** $zoom));
        $latRad = $lat * M_PI / 180;
        $y = (int) floor((1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * (2 ** $zoom));

        $tile = Http::withHeaders(['User-Agent' => 'georeference.it/1.0 (+https://georeference.it)'])
            ->timeout(8)
            ->get("https://tile.openstreetmap.org/{$zoom}/{$x}/{$y}.png");

        if (!$tile->ok()) {
            return response('', 502);
        }

        return response($tile->body(), 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
            'X-Tile-X'      => $x,
            'X-Tile-Y'      => $y,
            'X-Tile-Zoom'   => $zoom,
        ]);
    });
});
