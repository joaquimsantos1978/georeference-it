<?php

namespace App\Console\Commands;

use App\Models\GeorefValidation;
use App\Models\User;
use App\Models\UserBadge;
use App\Support\Badges;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

// Runs periodically rather than inline in the submit/validate controllers — keeps the
// hot request path untouched and the badge criteria (streaks, continent spread) easier
// to evaluate as batch queries than to track incrementally.
class AwardBadges extends Command
{
    protected $signature = 'badges:award';

    protected $description = 'Check recently-active users against the badge criteria and award any newly earned ones';

    private const NIGHT_OWL_THRESHOLD = 10;
    private const HUMBOLDT_THRESHOLD = 20;
    private const HUMBOLDT_MIN_GROUP_SIZE = 20;
    private const HUMBOLDT_MAX_UNCERTAINTY_M = 500;
    private const LINNAEUS_THRESHOLD = 25;
    private const DARWIN_CONTINENTS = 5;
    private const WALLACE_STREAK_DAYS = 7;

    public function handle(): int
    {
        // Only re-check users who did something recently — badge state never changes
        // for someone who hasn't been active, so there's no reason to scan everyone.
        $userIds = DB::table('activity_log')
            ->where('created_at', '>=', now()->subDay())
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id');

        $awarded = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if (!$user) {
                continue;
            }

            $already = $user->badges()->pluck('badge_key')->all();

            if (!in_array('linnaeus', $already) && $this->qualifiesLinnaeus($userId)) {
                $this->award($user, 'linnaeus');
                $awarded++;
            }
            if (!in_array('darwin', $already) && $this->qualifiesDarwin($userId)) {
                $this->award($user, 'darwin');
                $awarded++;
            }
            if (!in_array('humboldt', $already) && $this->qualifiesHumboldt($userId)) {
                $this->award($user, 'humboldt');
                $awarded++;
            }
            if (!in_array('wallace', $already) && $this->qualifiesWallace($userId)) {
                $this->award($user, 'wallace');
                $awarded++;
            }
            if (!in_array('coruja_noturna', $already) && $this->qualifiesCorujaNoturna($user)) {
                $this->award($user, 'coruja_noturna');
                $awarded++;
            }
        }

        $this->info("Checked {$userIds->count()} recently-active users, awarded {$awarded} new badge(s)");

        return self::SUCCESS;
    }

    private function qualifiesLinnaeus(int $userId): bool
    {
        return GeorefValidation::where('user_id', $userId)->count() >= self::LINNAEUS_THRESHOLD;
    }

    private function qualifiesDarwin(int $userId): bool
    {
        $continents = DB::table('georef_suggestions')
            ->join('locality_groups', 'locality_groups.id', '=', 'georef_suggestions.locality_group_id')
            ->where('georef_suggestions.user_id', $userId)
            ->where('georef_suggestions.status', 'validated')
            ->whereNotNull('locality_groups.continent')
            ->select('locality_groups.continent')
            ->distinct()
            ->count();

        return $continents >= self::DARWIN_CONTINENTS;
    }

    private function qualifiesHumboldt(int $userId): bool
    {
        $count = DB::table('georef_suggestions')
            ->join('locality_groups', 'locality_groups.id', '=', 'georef_suggestions.locality_group_id')
            ->where('georef_suggestions.user_id', $userId)
            ->where('georef_suggestions.status', 'validated')
            ->where('locality_groups.occurrence_count', '>=', self::HUMBOLDT_MIN_GROUP_SIZE)
            ->whereNotNull('georef_suggestions.coordinate_uncertainty_m')
            ->where('georef_suggestions.coordinate_uncertainty_m', '<=', self::HUMBOLDT_MAX_UNCERTAINTY_M)
            ->count();

        return $count >= self::HUMBOLDT_THRESHOLD;
    }

    private function qualifiesWallace(int $userId): bool
    {
        $days = DB::table('activity_log')
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(60))
            ->selectRaw('DISTINCT DATE(created_at) as d')
            ->pluck('d')
            ->map(fn($d) => \Carbon\Carbon::parse($d))
            ->sort()
            ->values();

        $streak = 1;
        for ($i = 1; $i < $days->count(); $i++) {
            if ($days[$i]->diffInDays($days[$i - 1]) === 1) {
                $streak++;
                if ($streak >= self::WALLACE_STREAK_DAYS) {
                    return true;
                }
            } else {
                $streak = 1;
            }
        }

        return false;
    }

    private function qualifiesCorujaNoturna(User $user): bool
    {
        if (!$user->timezone) {
            return false; // can't judge "local night" without a timezone
        }

        $rows = DB::table('activity_log')
            ->where('user_id', $user->id)
            ->select('created_at')
            ->get();

        $nightCount = 0;
        foreach ($rows as $row) {
            $localHour = (int) \Carbon\Carbon::parse($row->created_at)
                ->setTimezone($user->timezone)
                ->format('G');
            if ($localHour >= 20 || $localHour < 8) {
                $nightCount++;
                if ($nightCount >= self::NIGHT_OWL_THRESHOLD) {
                    return true;
                }
            }
        }

        return false;
    }

    private function award(User $user, string $key): void
    {
        UserBadge::create([
            'user_id'   => $user->id,
            'badge_key' => $key,
            'earned_at' => now(),
        ]);

        // No pre-rendered 'message' here on purpose — this command runs without any
        // particular user's language preference, and baking translated text in at
        // award time would freeze the notification in whatever locale happened to be
        // active then. The navbar renders the message from 'badge_key' at view time,
        // in the viewer's current language.
        $user->notifications()->create([
            'type' => 'badge_earned',
            'data' => [
                'badge_key' => $key,
            ],
        ]);

        $this->info("Awarded '{$key}' to user #{$user->id}");
    }
}
