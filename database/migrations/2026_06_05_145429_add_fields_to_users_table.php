<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('user_level_id')->nullable()->constrained('user_levels')->nullOnDelete();
            $table->string('orcid')->nullable()->unique();
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->enum('role', ['user', 'moderator', 'admin'])->default('user');
            $table->integer('total_validated')->default(0);
            $table->string('avatar')->nullable();
            $table->string('bio')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['user_level_id']);
            $table->dropColumn([
                'user_level_id', 'orcid', 'provider', 'provider_id',
                'role', 'total_validated', 'avatar', 'bio'
            ]);
        });
    }
};