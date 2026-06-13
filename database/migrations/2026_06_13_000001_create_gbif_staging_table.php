<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gbif_staging', function (Blueprint $table) {
            $table->unsignedBigInteger('gbif_id')->primary();
            $table->string('dataset_key', 36)->nullable();
            $table->string('publishing_org_key', 36)->nullable();
            $table->string('basis_of_record', 50)->nullable();
            $table->string('institution_code')->nullable();
            $table->string('collection_code')->nullable();
            $table->string('catalog_number')->nullable();
            $table->text('recorded_by')->nullable();
            $table->string('event_date', 100)->nullable();
            $table->string('country', 100)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('state_province')->nullable();
            $table->string('county')->nullable();
            $table->string('municipality')->nullable();
            $table->text('verbatim_locality')->nullable();
            $table->string('island')->nullable();
            $table->string('island_group')->nullable();
            $table->string('water_body')->nullable();
            $table->string('scientific_name', 500)->nullable();
            $table->string('taxon_rank', 50)->nullable();
            $table->string('kingdom', 100)->nullable();
            $table->string('family', 100)->nullable();
            $table->boolean('has_coordinate')->default(false);
            $table->decimal('decimal_latitude', 10, 7)->nullable();
            $table->decimal('decimal_longitude', 10, 7)->nullable();
            $table->float('coordinate_uncertainty_m')->nullable();
            $table->string('geodetic_datum', 50)->nullable();
            $table->dateTime('synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gbif_staging');
    }
};
