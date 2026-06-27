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
            $table->string('normalized_locality', 500)->nullable()->after('verbatim_locality');
            $table->index(['normalized_locality', 'county', 'country_code'], 'lg_normalized_locality');
        });
    }

    public function down(): void
    {
        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropIndex('lg_normalized_locality');
            $table->dropColumn('normalized_locality');
        });
    }
};
