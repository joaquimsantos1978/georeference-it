<?php

namespace App\Support;

use App\Models\User;

// Turns the stored notification rows into what the navbar bell shows, in the reader's language
// (the rows hold only ids/counts — see Notifier). One extra query resolves every person named
// in the visible notifications.
class NotificationPresenter
{
    // How many names are spelled out before collapsing the rest into "+N".
    private const NAMES_SHOWN = 2;

    /**
     * @return array<int, array{id:int, read:bool, time:string, title:string, lines:string[], link:bool}>
     */
    public static function latest(User $user, int $limit = 15): array
    {
        $rows = $user->notifications()
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $actorIds = [];
        foreach ($rows as $row) {
            foreach (($row->data['voters'] ?? []) as [$id]) {
                $actorIds[] = $id;
            }
            foreach (($row->data['commenters'] ?? []) as $id) {
                $actorIds[] = $id;
            }
        }

        $people = $actorIds
            ? User::whereIn('id', array_unique($actorIds))->get(['id', 'name', 'public_name'])->keyBy('id')
            : collect();

        return $rows->map(fn($n) => self::present($n, $people))->all();
    }

    private static function present($n, $people): array
    {
        $d      = $n->data ?? [];
        $lines  = [];
        $link   = true;
        $place  = $d['locality'] ?? '';

        switch ($n->type) {
            case 'vote':
                $title  = __('New votes on your georeference at :locality', ['locality' => $place]);
                $agree = $disagree = [];
                foreach (array_reverse($d['voters'] ?? []) as [$id, $vote]) {   // newest first
                    if ($vote === 'agree') {
                        $agree[] = $id;
                    } else {
                        $disagree[] = $id;
                    }
                }
                if ($agree) {
                    $lines[] = __('Agreed:') . ' ' . self::names($agree, $people);
                }
                if ($disagree) {
                    $lines[] = __('Disagreed:') . ' ' . self::names($disagree, $people);
                }
                break;

            case 'comment':
                $title   = __('New comments at :locality', ['locality' => $place]);
                $lines[] = __('By:') . ' ' . self::names(array_reverse($d['commenters'] ?? []), $people);
                if (!empty($d['snippet'])) {
                    $lines[] = '“' . $d['snippet'] . '”';
                }
                break;

            case 'suggestion_validated':
                $title = __('Your georeference at :locality was validated by the community', ['locality' => $place]);
                break;

            case 'suggestion_conflicted':
                $title = __('The community disagreed with your georeference at :locality', ['locality' => $place]);
                break;

            case 'weekly_digest':
                $title   = __('Your week on georeference.it');
                $lines[] = implode(' · ', [
                    __('Georefs') . ': ' . ($d['suggestions'] ?? 0),
                    __('Reviews') . ': ' . ($d['validations'] ?? 0),
                    __('Specimens') . ': ' . ($d['specimens'] ?? 0),
                    __('Validated') . ': ' . ($d['validated'] ?? 0),
                ]);
                $lines[] = ($d['totalGeoreferenced'] ?? 0) . ' ' . __('new specimens georeferenced this week')
                    . ' · ' . ($d['totalContributors'] ?? 0) . ' ' . __('active contributors this week');
                break;

            default:
                // badge_earned renders from its key in the reader's language; level_up / progress
                // (and anything older) carry their own message. These point nowhere.
                $link  = false;
                $badge = isset($d['badge_key']) ? Badges::get($d['badge_key']) : null;
                $title = $badge
                    ? __('New badge: :name — :description', ['name' => $badge['name'], 'description' => $badge['description']])
                    : ($d['message'] ?? '');
        }

        return [
            'id'    => $n->id,
            'read'  => $n->read_at !== null,
            'time'  => $n->updated_at->diffForHumans(),
            'title' => $title,
            'lines' => $lines,
            'link'  => $link,
        ];
    }

    // "Ana, Bruno +3" — language-neutral, so no plural forms to translate. People who keep
    // their name private (or whose account is gone) show as "Hidden contributor", as in comments.
    private static function names(array $ids, $people): string
    {
        $shown = array_map(function ($id) use ($people) {
            $person = $people->get($id);

            return $person && $person->public_name ? $person->name : __('Hidden contributor');
        }, array_slice($ids, 0, self::NAMES_SHOWN));

        $rest = count($ids) - self::NAMES_SHOWN;

        return implode(', ', $shown) . ($rest > 0 ? " +{$rest}" : '');
    }
}
