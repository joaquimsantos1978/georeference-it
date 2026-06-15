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
            $table->unsignedInteger('ungeoreferenced_count')->default(0)->after('occurrence_count');
            $table->index('ungeoreferenced_count', 'idx_lg_ungeoreferenced_count');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropIndex('idx_lg_ungeoreferenced_count');
            $table->dropColumn('ungeoreferenced_count');
        });
    }
};
