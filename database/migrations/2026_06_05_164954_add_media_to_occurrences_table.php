<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->json('media')->nullable()->after('gbif_coordinate_uncertainty_m');
            $table->string('scientific_name')->nullable()->after('media');
            $table->string('taxon_rank')->nullable()->after('scientific_name');
            $table->string('kingdom')->nullable()->after('taxon_rank');
            $table->string('family')->nullable()->after('kingdom');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropColumn(['media', 'scientific_name', 'taxon_rank', 'kingdom', 'family']);
        });
    }
};