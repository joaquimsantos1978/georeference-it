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
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['georef', 'validation_agree', 'validation_disagree', 'validation_abstain']);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('locality_group_id')->nullable();
            $table->unsignedInteger('occ_count')->default(1);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->unsignedInteger('uncertainty_m')->nullable();
            $table->text('remarks')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('location_label')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
            $table->index(['country_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_log');
    }
};
