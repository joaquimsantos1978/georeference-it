<?php

namespace Database\Seeders;

use App\Models\UserLevel;
use Illuminate\Database\Seeder;

class UserLevelSeeder extends Seeder
{
    public function run(): void
    {
        $levels = [
            [
                'name' => 'Beginner',
                'min_validated' => 0,
                'vote_weight' => 10,
                'sort_order' => 1,
            ],
            [
                'name' => 'Contributor',
                'min_validated' => 10,
                'vote_weight' => 20,
                'sort_order' => 2,
            ],
            [
                'name' => 'Experienced',
                'min_validated' => 50,
                'vote_weight' => 30,
                'sort_order' => 3,
            ],
            [
                'name' => 'Expert',
                'min_validated' => 200,
                'vote_weight' => 50,
                'sort_order' => 4,
            ],
        ];

        foreach ($levels as $level) {
            UserLevel::updateOrCreate(
                ['name' => $level['name']],
                $level
            );
        }
    }
}