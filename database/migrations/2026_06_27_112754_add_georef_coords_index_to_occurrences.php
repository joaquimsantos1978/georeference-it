<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            // Speeds up: WHERE locality_group_id = ? AND gbif_decimal_latitude IS NOT NULL
            // Used by groupData() for georef occurrence queries
            $table->index(['locality_group_id', 'gbif_decimal_latitude'], 'occurrences_group_gbif_lat');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropIndex('occurrences_group_gbif_lat');
        });
    }
};
