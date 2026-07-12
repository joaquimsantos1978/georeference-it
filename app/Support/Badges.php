<?php

namespace App\Support;

// Curated, fixed badge set — small enough that a DB-backed admin UI isn't worth it yet.
// Deliberately separate from UserLevel (which already owns validated-count thresholds):
// these reward breadth/consistency/difficulty instead of raw volume.
class Badges
{
    public const DEFINITIONS = [
        'linnaeus' => [
            'name'        => 'Linnaeus',
            'icon'        => '🏷️',
            'description' => 'Deu 25 votos de validação a sugestões de outros contribuidores.',
        ],
        'magalhaes' => [
            'name'        => 'Magalhães',
            'icon'        => '🧭',
            'description' => 'Teve sugestões validadas em pelo menos 5 continentes diferentes.',
        ],
        'livingstone' => [
            'name'        => 'Livingstone',
            'icon'        => '🔎',
            'description' => 'Resolveu 20 localidades difíceis com precisão (grupos grandes, coordenadas de baixa incerteza).',
        ],
        'shackleton' => [
            'name'        => 'Shackleton',
            'icon'        => '🏕️',
            'description' => 'Contribuiu em 7 dias seguidos.',
        ],
        'coruja_noturna' => [
            'name'        => 'Coruja Noturna',
            'icon'        => '🦉',
            'description' => 'Contribuiu 10 ou mais vezes fora do horário 8h–20h (hora local).',
        ],
    ];

    public static function get(string $key): ?array
    {
        return self::DEFINITIONS[$key] ?? null;
    }
}
