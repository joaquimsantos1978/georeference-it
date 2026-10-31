<?php

namespace App\Jobs;

use App\Models\GeorefSuggestion;
use App\Models\LocalityGroup;
use App\Models\Occurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

// Mark / unmark a whole locality group as "not georeferenceable".
//
// Both the bulk occurrence UPDATE and LocalityGroup::recalculateCounters() have to touch every
// occurrence of the group (deleted_at is not in the status index). On the huge catch-all groups
// (hundreds of thousands of rows) that takes minutes, far past the web request's 20s
// max_statement_time, so the user's request used to fail with a 500 after the change was
// partly or fully saved. GeorefController therefore runs this synchronously for normal groups
// and hands it to the queue worker (no statement limit) for big ones.
class ApplyNotGeoreferenceableToGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Above this many occurrences the work goes to the queue instead of the web request.
    public const QUEUE_THRESHOLD = 20000;

    public int $tries = 1;
    public int $timeout = 1800;

    public function __construct(
        public readonly int $groupId,
        public readonly bool $mark,
        public readonly ?int $userId = null,
    ) {}

    public function handle(): void
    {
        $group = LocalityGroup::find($this->groupId);
        if (!$group) {
            return;
        }

        if ($this->mark) {
            // "Not georeferenceable" also means the automatic proposals for this locality are
            // meaningless: drop the pending system suggestions, otherwise the API keeps serving
            // one of them as the coordinates of every gbif_georeferenced/has_suggestion
            // occurrence in the group (the update below deliberately leaves those occurrences
            // alone). Human suggestions are left untouched.
            GeorefSuggestion::where('locality_group_id', $group->id)
                ->where('status', 'pending')
                ->whereNull('user_id')
                ->update(['status' => 'rejected']);

            Occurrence::where('locality_group_id', $group->id)
                ->whereIn('georef_status', ['ungeoreferenced', 'has_suggestion'])
                ->update([
                    'georef_status' => 'not_georeferenceable',
                    'not_georeferenceable_by' => $this->userId,
                    'not_georeferenceable_at' => now(),
                ]);
        } else {
            Occurrence::where('locality_group_id', $group->id)
                ->where('georef_status', 'not_georeferenceable')
                ->update([
                    'georef_status' => 'ungeoreferenced',
                    'not_georeferenceable_by' => null,
                    'not_georeferenceable_at' => null,
                ]);
        }

        $group->recalculateCounters();
    }
}
