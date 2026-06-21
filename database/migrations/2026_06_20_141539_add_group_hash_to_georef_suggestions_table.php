<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->string('locality_group_hash', 40)->nullable()->after('locality_group_id')->index();
        });

        DB::statement('
            UPDATE georef_suggestions gs
            JOIN locality_groups lg ON lg.id = gs.locality_group_id
            SET gs.locality_group_hash = lg.group_hash
        ');
    }

    public function down(): void
    {
        Schema::table('georef_suggestions', function (Blueprint $table) {
            $table->dropIndex(['locality_group_hash']);
            $table->dropColumn('locality_group_hash');
        });
    }
};
