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
        Schema::table('locality_groups', function (Blueprint $table) {
            // Covers: WHERE country_code=? AND ungeoreferenced_count>0 ORDER BY occurrence_count DESC
            $table->index(['country_code', 'ungeoreferenced_count', 'occurrence_count'], 'idx_lg_country_ungeoref_occ');
            // Covers: WHERE country_code=? AND pending_count>0 ORDER BY occurrence_count DESC
            $table->index(['country_code', 'pending_count', 'occurrence_count'], 'idx_lg_country_pending_occ');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropIndex('idx_lg_country_ungeoref_occ');
            $table->dropIndex('idx_lg_country_pending_occ');
        });
    }
};
