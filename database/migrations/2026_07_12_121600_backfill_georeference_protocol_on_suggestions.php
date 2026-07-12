<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// georef_suggestions is orders of magnitude smaller than occurrences — a plain
// UPDATE is safe here, unlike the ALTER TABLE caution needed on occurrences.
return new class extends Migration
{
    private const OLD = 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020)';
    private const NEW = 'Georeferencing Quick Reference Guide (Zermoglio et al. 2020, https://doi.org/10.35035/e09p-h128)';

    public function up(): void
    {
        DB::table('georef_suggestions')
            ->where('georeference_protocol', self::OLD)
            ->update(['georeference_protocol' => self::NEW]);
    }

    public function down(): void
    {
        DB::table('georef_suggestions')
            ->where('georeference_protocol', self::NEW)
            ->update(['georeference_protocol' => self::OLD]);
    }
};
