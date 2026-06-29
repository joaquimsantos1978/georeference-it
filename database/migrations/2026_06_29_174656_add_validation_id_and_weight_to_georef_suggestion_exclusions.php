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
        Schema::table('georef_suggestion_exclusions', function (Blueprint $table) {
            $table->foreignId('validation_id')->nullable()->after('suggestion_id')
                ->constrained('georef_validations')->nullOnDelete();
            $table->unsignedSmallInteger('weight')->default(1)->after('occurrence_id');
        });
    }

    public function down(): void
    {
        Schema::table('georef_suggestion_exclusions', function (Blueprint $table) {
            $table->dropForeign(['validation_id']);
            $table->dropColumn(['validation_id', 'weight']);
        });
    }
};
