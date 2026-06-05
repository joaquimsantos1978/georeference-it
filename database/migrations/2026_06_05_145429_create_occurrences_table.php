<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('occurrences', function (Blueprint $table) {
            $table->id();
            $table->string('gbif_occurrence_key')->unique();
            $table->string('dataset_key')->nullable()->index();
            $table->string('publisher_key')->nullable()->index();
            $table->string('catalog_number')->nullable();
            $table->string('institution_code')->nullable();
            $table->string('collection_code')->nullable();
            $table->string('basis_of_record')->nullable();
            $table->string('verbatim_locality')->nullable();
            $table->string('country')->nullable();
            $table->string('country_code', 2)->nullable()->index();
            $table->string('state_province')->nullable();
            $table->string('county')->nullable();
            $table->string('municipality')->nullable();
            $table->string('island')->nullable();
            $table->string('island_group')->nullable();
            $table->string('water_body')->nullable();
            $table->string('higher_geography')->nullable();
            $table->string('event_date')->nullable();
            $table->string('recorded_by')->nullable();
            $table->string('field_number')->nullable();
            $table->decimal('gbif_decimal_latitude', 10, 7)->nullable();
            $table->decimal('gbif_decimal_longitude', 10, 7)->nullable();
            $table->string('gbif_geodetic_datum')->nullable();
            $table->integer('gbif_coordinate_uncertainty_m')->nullable();
            $table->foreignId('locality_group_id')->nullable()->constrained('locality_groups')->nullOnDelete();
            $table->enum('georef_status', [
                'ungeoreferenced',
                'has_suggestion',
                'validated',
                'gbif_georeferenced',
                'gbif_reviewed',
                'conflicted'
            ])->default('ungeoreferenced');
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('occurrences');
    }
};