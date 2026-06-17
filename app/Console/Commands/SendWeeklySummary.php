<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummary;
use App\Models\GeorefSuggestion;
use App\Models\GeorefValidation;
use App\Models\LocalityGroupComment;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummary extends Command
{
    protected $signature = 'app:send-weekly-summary';
    protected $description = 'Send weekly activity summary to opted-in users';

    public function handle(): void
    {
        $since = now()->subWeek();

        $totalGeoreferenced = GeorefSuggestion::where('created_at', '>=', $since)->count();

        $totalContributors = User::whereHas('suggestions', fn($q) => $q->where('created_at', '>=', $since))
            ->orWhereHas('validations', fn($q) => $q->where('created_at', '>=', $since))
            ->count();

        $users = User::where('email_notifications', true)->get();

        foreach ($users as $user) {
            $suggestions = GeorefSuggestion::where('user_id', $user->id)
                ->where('created_at', '>=', $since)->count();

            $validations = GeorefValidation::where('user_id', $user->id)
                ->where('created_at', '>=', $since)->count();

            $comments = LocalityGroupComment::where('user_id', $user->id)
                ->where('created_at', '>=', $since)->count();

            if ($suggestions + $validations + $comments === 0) {
                continue;
            }

            Mail::to($user->email)->queue(new WeeklySummary(
                user: $user,
                suggestions: $suggestions,
                validations: $validations,
                comments: $comments,
                totalContributors: $totalContributors,
                totalGeoreferenced: $totalGeoreferenced,
            ));
        }

        $this->info('Weekly summary emails queued.');
    }
}
