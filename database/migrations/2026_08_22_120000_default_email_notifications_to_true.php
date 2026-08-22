<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// New accounts should start opted in to the weekly summary — existing users made an
// implicit choice under the old opt-out-by-default column and are left as-is here, only the
// column default changes for rows created from now on. Two creation paths (RegisteredUserController,
// SocialiteController) both rely on this DB-level default rather than each setting it explicitly.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function ($table) {
            $table->boolean('email_notifications')->default(true)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function ($table) {
            $table->boolean('email_notifications')->default(false)->change();
        });
    }
};
