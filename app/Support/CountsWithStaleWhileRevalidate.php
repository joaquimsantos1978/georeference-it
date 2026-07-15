<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

// Shared by ExploreController and ActivityController: a plain Cache::remember() means
// whichever request lands right after TTL expiry pays the live query's cost inline —
// fine for a cheap query, but observed causing 504s on Explore's multi-minute COUNT(*).
// This never blocks a request on a live compute except the very first time a given cache
// key is ever populated: once something is cached, every later request — including the
// one that notices it's stale — returns the cached value immediately and kicks the
// recompute off via afterResponse() (fastcgi_finish_request() under PHP-FPM, no queue
// worker needed).
trait CountsWithStaleWhileRevalidate
{
    private function countWithStaleWhileRevalidate(string $cacheKey, \Closure $compute, int $staleAfterSeconds = 3600): int
    {
        $dataKey = $cacheKey . ':data';
        $cached  = Cache::get($dataKey);

        if ($cached === null) {
            $lock = Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                try {
                    $cached = $compute();
                    Cache::forever($dataKey, $cached);
                    Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                } finally {
                    $lock->release();
                }
            } else {
                try {
                    $lock->block(10);
                    $lock->release();
                } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) {
                    // Still running after 10s — fall through to the safe default below.
                }
                $cached = Cache::get($dataKey);
            }

            return $cached ?? 0;
        }

        $isStale = Cache::get($cacheKey . ':computed_at', 0) < now()->subSeconds($staleAfterSeconds)->timestamp;
        if ($isStale) {
            $lock = Cache::lock($cacheKey . ':lock', 300);
            if ($lock->get()) {
                dispatch(function () use ($compute, $dataKey, $cacheKey, $lock) {
                    try {
                        Cache::forever($dataKey, $compute());
                        Cache::forever($cacheKey . ':computed_at', now()->timestamp);
                    } finally {
                        $lock->release();
                    }
                })->afterResponse();
            }
            // Lock already held (another request's afterResponse() job is mid-flight,
            // or hasn't run yet) — nothing to do here either way, this request still
            // just returns the stale value below like normal.
        }

        return $cached;
    }
}
