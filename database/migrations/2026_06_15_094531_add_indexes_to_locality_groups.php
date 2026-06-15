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
            $table->index('occurrence_count', 'idx_lg_occurrence_count');
            $table->index(['pending_count', 'validated_count'], 'idx_lg_activity');
            $table->index('country_code', 'idx_lg_country_code');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropIndex('idx_lg_occurrence_count');
            $table->dropIndex('idx_lg_activity');
            $table->dropIndex('idx_lg_country_code');
        });
    }
};
