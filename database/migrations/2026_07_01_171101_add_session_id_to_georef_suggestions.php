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
            $table->string('session_id', 40)->nullable()->after('anon_name')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->dropColumn('session_id');
        });
    }
};
