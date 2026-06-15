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
            // Composite indexes for the /next query — avoids OR + filesort on 43M rows
            $table->index(['ungeoreferenced_count', 'occurrence_count'], 'idx_lg_ungeoref_occ');
            $table->index(['pending_count', 'occurrence_count'], 'idx_lg_pending_occ');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropIndex('idx_lg_ungeoref_occ');
            $table->dropIndex('idx_lg_pending_occ');
        });
    }
};
