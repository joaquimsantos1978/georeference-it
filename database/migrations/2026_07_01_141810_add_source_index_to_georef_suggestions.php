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
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->index(['user_id', 'georeference_sources', 'created_at'], 'gs_user_source_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->dropIndex('gs_user_source_created_at');
        });
    }
};
