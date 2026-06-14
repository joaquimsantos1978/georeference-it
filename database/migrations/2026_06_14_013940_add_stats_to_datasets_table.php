<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->string('institution_code', 100)->nullable()->after('publisher_key');
            $table->string('collection_code', 100)->nullable()->after('institution_code');
            $table->unsignedInteger('total')->default(0)->after('collection_code');
            $table->unsignedInteger('georeferenced')->default(0)->after('total');
            $table->unsignedInteger('validated')->default(0)->after('georeferenced');
            $table->unsignedInteger('ungeoreferenced')->default(0)->after('validated');
            $table->timestamp('stats_updated_at')->nullable()->after('ungeoreferenced');
        });
    }

    public function down(): void
    {
        Schema::table('datasets', function (Blueprint $table) {
            $table->dropColumn(['institution_code', 'collection_code', 'total', 'georeferenced', 'validated', 'ungeoreferenced', 'stats_updated_at']);
        });
    }
};
