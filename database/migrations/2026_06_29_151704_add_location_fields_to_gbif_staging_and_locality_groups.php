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
        Schema::table('gbif_staging', function (Blueprint $table) {
            $table->string('continent', 100)->nullable()->after('water_body');
            $table->text('higher_geography')->nullable()->after('continent');
            $table->text('location_remarks')->nullable()->after('higher_geography');
            $table->string('locality', 1000)->nullable()->change(); // ensure enough space
        });

        Schema::table('locality_groups', function (Blueprint $table) {
            $table->string('continent', 100)->nullable()->after('country_code');
            $table->text('higher_geography')->nullable()->after('continent');
            $table->text('location_remarks')->nullable()->after('verbatim_locality');
        });
    }

    public function down(): void
    {
        Schema::table('gbif_staging', function (Blueprint $table) {
            $table->dropColumn(['continent', 'higher_geography', 'location_remarks']);
        });

        Schema::table('locality_groups', function (Blueprint $table) {
            $table->dropColumn(['continent', 'higher_geography', 'location_remarks']);
        });
    }
};
