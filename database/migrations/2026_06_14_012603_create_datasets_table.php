<?php
// Stores GBIF dataset metadata (title, publisher) for search on the /datasets page.
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
        Schema::create('datasets', function (Blueprint $table) {
            $table->string('key', 36)->primary();
            $table->string('title')->nullable();
            $table->string('publisher_name')->nullable();
            $table->string('publisher_key', 36)->nullable();
            $table->string('license')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('datasets');
    }
};
