<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('georef_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suggestion_id')->constrained('georef_suggestions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('vote', ['agree', 'disagree', 'abstain'])->default('agree');
            $table->integer('points_awarded')->default(0);
            $table->timestamps();

            $table->unique(['suggestion_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('georef_validations');
    }
};