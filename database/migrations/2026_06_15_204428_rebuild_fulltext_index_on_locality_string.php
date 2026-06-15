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
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE locality_groups DROP INDEX ft_locality');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE locality_groups ADD FULLTEXT INDEX ft_locality (locality_string)');
    }

    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE locality_groups DROP INDEX ft_locality');
        \Illuminate\Support\Facades\DB::statement('ALTER TABLE locality_groups ADD FULLTEXT INDEX ft_locality (verbatim_locality, municipality, county, state_province, locality_string)');
    }
};
