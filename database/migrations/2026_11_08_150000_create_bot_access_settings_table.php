<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_access_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('mode')->default('public');
            // Always stored in UTC, independently of the application timezone.
            $table->dateTime('restricted_until')->nullable();
            $table->string('timezone')->default('Europe/Rome');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_access_settings');
    }
};
