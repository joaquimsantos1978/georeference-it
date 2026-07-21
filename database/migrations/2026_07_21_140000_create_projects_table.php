<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('tags')->nullable();
            $table->string('image')->nullable();
            $table->enum('visibility', ['public', 'private'])->default('private');
            $table->enum('mode', ['criteria', 'id_list']);
            // mode=criteria: [{field, operator, value}, ...], AND-only
            $table->json('criteria')->nullable();
            // mode=id_list: raw pasted gbif_occurrence_key list
            $table->json('submitted_keys')->nullable();
            // mode=id_list: subset of submitted_keys not matched at last validation
            $table->json('invalid_keys')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['visibility', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
