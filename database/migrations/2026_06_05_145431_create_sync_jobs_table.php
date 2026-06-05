<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_session_id')->nullable()->constrained('work_sessions')->nullOnDelete();
            $table->string('country_code', 2)->nullable();
            $table->string('dataset_key')->nullable();
            $table->decimal('bbox_north', 10, 7)->nullable();
            $table->decimal('bbox_south', 10, 7)->nullable();
            $table->decimal('bbox_east', 10, 7)->nullable();
            $table->decimal('bbox_west', 10, 7)->nullable();
            $table->integer('fetched_count')->default(0);
            $table->integer('total_count')->nullable();
            $table->integer('offset')->default(0);
            $table->enum('status', [
                'pending',
                'running',
                'completed',
                'failed'
            ])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};