<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            // Composite index used by whereHas + checkConsistency/createAutoSuggestions
            $table->index(['locality_group_id', 'georef_status'], 'occurrences_group_georef_status');
            $table->index('georef_status', 'occurrences_georef_status');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropIndex('occurrences_group_georef_status');
            $table->dropIndex('occurrences_georef_status');
        });
    }
};
