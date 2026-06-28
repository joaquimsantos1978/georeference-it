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
        Schema::table('activity_log', function (Blueprint $table) {
            // 'user' = authenticated, 'anonymous' = unauthenticated human, 'system' = auto-generated
            $table->enum('source', ['user', 'anonymous', 'system'])->default('user')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
