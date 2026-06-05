<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locality_groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_hash', 40)->unique();
            $table->text('locality_string')->nullable();
            $table->string('verbatim_locality')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('state_province')->nullable();
            $table->string('county')->nullable();
            $table->string('municipality')->nullable();
            $table->integer('occurrence_count')->default(0);
            $table->integer('pending_count')->default(0);
            $table->integer('validated_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('locality_groups');
    }
};