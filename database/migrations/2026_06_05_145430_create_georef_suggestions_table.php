<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('georef_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('occurrence_id')->constrained('occurrences')->cascadeOnDelete();
            $table->foreignId('locality_group_id')->nullable()->constrained('locality_groups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anon_name')->nullable();
            $table->decimal('decimal_latitude', 10, 7);
            $table->decimal('decimal_longitude', 10, 7);
            $table->string('geodetic_datum')->default('epsg:4326');
            $table->integer('coordinate_uncertainty_m')->nullable();
            $table->decimal('coordinate_precision', 10, 7)->nullable();
            $table->decimal('point_radius_spatial_fit', 8, 3)->nullable();
            $table->longText('footprint_wkt')->nullable();
            $table->string('footprint_srs')->nullable();
            $table->decimal('footprint_spatial_fit', 8, 3)->nullable();
            $table->string('location_id')->nullable();
            $table->string('georeference_protocol')->nullable();
            $table->string('georeference_sources')->nullable();
            $table->text('georeference_remarks')->nullable();
            $table->string('share_link')->nullable();
            $table->enum('status', [
                'pending',
                'validated',
                'rejected',
                'superseded'
            ])->default('pending');
            $table->integer('total_points')->default(0);
            $table->timestamp('georeferenced_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('georef_suggestions');
    }
};