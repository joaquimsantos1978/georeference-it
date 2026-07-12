<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Needed for the "Coruja Noturna" (night owl) badge — activity timestamps are stored
// in UTC, so scoring "late at night" requires knowing each user's local timezone.
// Auto-detected client-side (Intl.DateTimeFormat) rather than asked explicitly.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('timezone', 64)->nullable()->after('locale');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
