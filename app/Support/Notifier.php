<?php

namespace App\Support;

use App\Models\GeorefSuggestion;
use App\Models\LocalityGroup;
use App\Models\LocalityGroupComment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Creates the bell notifications (rows in `notifications`).
//
// Activity on a locality group can come in bursts (ten people voting on the same suggestion,
// a comment thread), so related events are GROUPED: while a notification of the same type and
// target is still unread, new events are merged into it (actors appended, bumped to the top)
// instead of adding a row per event. Once the user has read it, the next event starts a fresh
// one. Only language-neutral data is stored (ids, counts, the place name); the sentence is
// built by NotificationPresenter in the reader's language when the bell is rendered.
//
// None of this may ever break the action that triggered it (a vote, a comment, a validation),
// so every public method swallows and logs its own failures.
class Notifier
{
    // Cap on remembered actors per grouped notification — the bell only names the last two.
    private const MAX_ACTORS = 200;

    // Someone voted on a suggestion of $suggestion's author. A user who flips their vote moves
    // to the "latest" position with their new vote rather than counting twice.
    public static function vote(GeorefSuggestion $suggestion, User $voter, string $vote): void
    {
        $authorId = $suggestion->user_id;
        if (!$authorId || $authorId === $voter->id) {
            return;
        }

        self::guard(function () use ($suggestion, $voter, $vote, $authorId) {
            $locality = self::label($suggestion->localityGroup);

            self::upsertUnread([$authorId], 'vote', 'suggestion_id', $suggestion->id, function (array $data) use ($suggestion, $voter, $vote, $locality) {
                return [
                    'suggestion_id' => $suggestion->id,
                    'group_id'      => $suggestion->locality_group_id,
                    'locality'      => $locality,
                    'voters'        => self::appendActor($data['voters'] ?? [], [$voter->id, $vote]),
                ];
            });
        });
    }

    // A comment was posted in a group; $recipientIds are the people already taking part in it
    // (the same set that gets the e-mail), minus the author.
    public static function comment(LocalityGroup $group, LocalityGroupComment $comment, array $recipientIds): void
    {
        if (!$recipientIds) {
            return;
        }

        self::guard(function () use ($group, $comment, $recipientIds) {
            $locality = self::label($group);
            $snippet  = Str::limit(trim(preg_replace('/\s+/', ' ', (string) $comment->body)), 90);

            self::upsertUnread($recipientIds, 'comment', 'group_id', $group->id, function (array $data) use ($group, $comment, $locality, $snippet) {
                return [
                    'group_id'   => $group->id,
                    'locality'   => $locality,
                    'commenters' => self::appendActor($data['commenters'] ?? [], $comment->user_id),
                    'count'      => (int) ($data['count'] ?? 0) + 1,
                    'snippet'    => $snippet,
                ];
            });
        });
    }

    // The community validated / disputed a suggestion: tell its (human) author.
    // $type is 'suggestion_validated' or 'suggestion_conflicted'.
    public static function suggestionOutcome(GeorefSuggestion $suggestion, string $type): void
    {
        if (!$suggestion->user_id) {
            return;
        }

        self::guard(function () use ($suggestion, $type) {
            $locality = self::label($suggestion->localityGroup);

            self::upsertUnread([$suggestion->user_id], $type, 'suggestion_id', $suggestion->id, fn(array $data) => [
                'suggestion_id' => $suggestion->id,
                'group_id'      => $suggestion->locality_group_id,
                'locality'      => $locality,
            ]);
        });
    }

    // One per user per week, same figures as the e-mail (see SendWeeklySummary).
    public static function weeklyDigest(User $user, array $stats): void
    {
        self::guard(fn() => Notification::create([
            'user_id' => $user->id,
            'type'    => 'weekly_digest',
            'data'    => $stats,
        ]));
    }

    // Human-readable place name, same precedence the activity feed uses.
    public static function label(?LocalityGroup $group): string
    {
        if (!$group) {
            return '';
        }

        return trim(implode(', ', array_filter([$group->verbatim_locality, $group->municipality, $group->county])))
            ?: (string) $group->locality_string;
    }

    // Appends $actor to an ordered list, dropping any earlier entry of the same actor so the
    // newest is always last. An actor is a user id, or [user id, vote] for votes.
    private static function appendActor(array $list, $actor): array
    {
        $id   = is_array($actor) ? $actor[0] : $actor;
        $list = array_values(array_filter($list, fn($a) => (is_array($a) ? $a[0] : $a) !== $id));
        $list[] = $actor;

        return array_slice($list, -self::MAX_ACTORS);
    }

    // For each user: merge into their unread notification of $type about $matchKey=$matchValue
    // (built from its current data by $build) or create a new one. Two queries regardless of
    // how many recipients, because a busy group can have hundreds of participants.
    private static function upsertUnread(array $userIds, string $type, string $matchKey, int $matchValue, \Closure $build): void
    {
        $existing = Notification::whereIn('user_id', $userIds)
            ->where('type', $type)
            ->whereNull('read_at')
            ->where("data->{$matchKey}", $matchValue)
            ->get()
            ->keyBy('user_id');

        $now  = now();
        $rows = [];

        foreach ($userIds as $userId) {
            if ($notification = $existing->get($userId)) {
                $notification->update(['data' => $build($notification->data ?? [])]);
                continue;
            }

            $rows[] = [
                'user_id'    => $userId,
                'type'       => $type,
                'data'       => json_encode($build([])),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            Notification::insert($rows);
        }
    }

    private static function guard(\Closure $work): void
    {
        try {
            $work();
        } catch (\Throwable $e) {
            Log::warning('Notification not created: ' . $e->getMessage());
        }
    }
}
