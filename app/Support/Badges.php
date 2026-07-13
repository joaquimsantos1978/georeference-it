<?php

namespace App\Support;

// Curated, fixed badge set — small enough that a DB-backed admin UI isn't worth it yet.
// Deliberately separate from UserLevel (which already owns validated-count thresholds):
// these reward breadth/consistency/difficulty instead of raw volume.
//
// All named after naturalists (not generic explorers) — e.g. Darwin over Magalhães —
// per explicit steer: the theme is natural history, not exploration in general.
class Badges
{
    // 'initials'/icon rendering is still being redesigned (<x-badge-icon> WIP) — the
    // definitions below are the source of truth for names/criteria in the meantime.
    // 'name'/'description' are source (English) strings — always pass through __()
    // at display time (see get()) rather than reading them raw, so they follow the
    // viewer's current language instead of whatever locale was active when a badge
    // definition was written or a notification was created.
    public const DEFINITIONS = [
        'linnaeus' => [
            'name'        => 'Linnaeus',
            'initials'    => 'LN',
            'color'       => '#16a34a',
            'description' => 'Gave 25 validation votes on other contributors\' suggestions.',
        ],
        'darwin' => [
            'name'        => 'Darwin',
            'initials'    => 'DW',
            'color'       => '#2563eb',
            'description' => 'Had suggestions validated on at least 5 different continents.',
        ],
        'humboldt' => [
            'name'        => 'Humboldt',
            'initials'    => 'HB',
            'color'       => '#b45309',
            'description' => 'Resolved 20 difficult localities with precision (large groups, low coordinate uncertainty).',
        ],
        'wallace' => [
            'name'        => 'Wallace',
            'initials'    => 'WL',
            'color'       => '#0891b2',
            'description' => 'Contributed on 7 consecutive days.',
        ],
        'coruja_noturna' => [
            'name'        => 'Night Owl',
            'initials'    => null,
            'color'       => '#4338ca',
            'description' => 'Contributed 10 or more times outside the 8am–8pm window (local time).',
        ],
    ];

    // Returns the definition with 'name'/'description' translated for the current
    // locale. Use this (not DEFINITIONS[$key] directly) anywhere the text is displayed.
    public static function get(string $key): ?array
    {
        $badge = self::DEFINITIONS[$key] ?? null;
        if ($badge === null) {
            return null;
        }

        $badge['name']        = __($badge['name']);
        $badge['description'] = __($badge['description']);

        return $badge;
    }
}
