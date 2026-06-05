<?php

namespace Database\Seeders;

use App\Models\PlatformSetting;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key' => 'validation_threshold',
                'value' => '60',
                'description' => 'Total points required to validate a georeferencing suggestion',
            ],
            [
                'key' => 'gbif_fetch_limit',
                'value' => '300',
                'description' => 'Maximum number of occurrences to fetch per GBIF API request',
            ],
            [
                'key' => 'sync_cache_days',
                'value' => '7',
                'description' => 'Number of days before a synced area is considered stale',
            ],
            [
                'key' => 'allow_anonymous_suggestions',
                'value' => '1',
                'description' => 'Allow users without an account to submit georeferencing suggestions',
            ],
            [
                'key' => 'anonymous_vote_weight',
                'value' => '0',
                'description' => 'Points awarded for anonymous suggestions (0 = does not count towards validation)',
            ],
            [
                'key' => 'app_name',
                'value' => 'georeference.it',
                'description' => 'Platform name displayed in the interface',
            ],
            [
                'key' => 'app_description',
                'value' => 'Crowdsourced georeferencing of natural history collection specimens',
                'description' => 'Platform description displayed in the interface',
            ],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}