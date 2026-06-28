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
            $table->index(['created_at'], 'gs_created_at');
            $table->index(['user_id', 'created_at'], 'gs_user_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->dropIndex('gs_created_at');
            $table->dropIndex('gs_user_created_at');
        });
    }
};
