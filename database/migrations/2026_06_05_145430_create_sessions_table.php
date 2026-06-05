<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('area_name')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('dataset_key')->nullable();
            $table->decimal('bbox_north', 10, 7)->nullable();
            $table->decimal('bbox_south', 10, 7)->nullable();
            $table->decimal('bbox_east', 10, 7)->nullable();
            $table->decimal('bbox_west', 10, 7)->nullable();
            $table->integer('occurrences_done')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_sessions');
    }
};