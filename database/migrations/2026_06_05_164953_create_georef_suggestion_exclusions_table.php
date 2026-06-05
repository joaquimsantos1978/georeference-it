<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('georef_suggestion_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->constrained('georef_suggestions')->cascadeOnDelete();
            $table->foreignId('occurrence_id')->constrained('occurrences')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['suggestion_id', 'occurrence_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('georef_suggestion_exclusions');
    }
};