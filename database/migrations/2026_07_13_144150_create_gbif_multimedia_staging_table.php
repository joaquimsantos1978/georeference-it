<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Replaces the old PHP-side stream+group+UPDATE...CASE multimedia import with the same
// LOAD DATA INFILE + staging-table + JOIN pattern already used for occurrence.txt
// (see GbifImportDownload::loadIntoStaging/processStaging) — consistent with the rest
// of the pipeline and avoids competing for I/O with live traffic the way the old
// 500-branch CASE UPDATEs did.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gbif_multimedia_staging', function (Blueprint $table) {
            $table->id();
            // Not unique — one occurrence can have several media rows (multiple images).
            $table->unsignedBigInteger('gbif_id')->index();
            $table->string('type', 50)->nullable();
            $table->string('format', 100)->nullable();
            $table->text('identifier')->nullable();
            $table->text('title')->nullable();
            $table->string('license', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gbif_multimedia_staging');
    }
};
