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
        Schema::table('occurrences', function (Blueprint $table) {
            $table->string('continent')->nullable()->after('higher_geography');
            $table->text('location_remarks')->nullable()->after('continent');
        });
    }

    public function down(): void
    {
        Schema::table('occurrences', function (Blueprint $table) {
            $table->dropColumn(['continent', 'location_remarks']);
        });
    }
};
