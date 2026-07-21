<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Adds the Event (year/month/day), Taxon (phylum/class/order/genus/specific_epithet) and
// Identification (type_status) Darwin Core groups — needed as criteria fields for thematic
// projects. All nullable with no default, so this qualifies for ALGORITHM=INSTANT (same as
// 2026_06_29_192921_add_continent_location_remarks_to_occurrences.php) — metadata-only, no
// table rewrite, safe on the full production table. Existing rows stay NULL until the next
// gbif:import-download/monthly-refresh reprocesses them (termMap() wiring is a separate
// change) — no special backfill import needed, the monthly refresh already reimports
// everything.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('event_date');
            $table->unsignedTinyInteger('month')->nullable()->after('year');
            $table->unsignedTinyInteger('day')->nullable()->after('month');
            $table->string('phylum')->nullable()->after('kingdom');
            $table->string('class')->nullable()->after('phylum');
            $table->string('order')->nullable()->after('class');
            $table->string('genus')->nullable()->after('family');
            $table->string('specific_epithet')->nullable()->after('genus');
            $table->string('type_status')->nullable()->after('specific_epithet');
        });

        Schema::table('gbif_staging', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->nullable()->after('event_date');
            $table->unsignedTinyInteger('month')->nullable()->after('year');
            $table->unsignedTinyInteger('day')->nullable()->after('month');
            $table->string('phylum', 100)->nullable()->after('kingdom');
            $table->string('class', 100)->nullable()->after('phylum');
            $table->string('order', 100)->nullable()->after('class');
            $table->string('genus', 100)->nullable()->after('family');
            $table->string('specific_epithet', 100)->nullable()->after('genus');
            $table->string('type_status', 255)->nullable()->after('specific_epithet');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day', 'phylum', 'class', 'order', 'genus', 'specific_epithet', 'type_status']);
        });

        Schema::table('gbif_staging', function (Blueprint $table) {
            $table->dropColumn(['year', 'month', 'day', 'phylum', 'class', 'order', 'genus', 'specific_epithet', 'type_status']);
        });
    }
};
