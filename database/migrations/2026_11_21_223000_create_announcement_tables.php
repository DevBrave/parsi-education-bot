<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->json('languages');
            $table->boolean('without_program')->default(false);
            $table->unsignedInteger('minimum_submissions')->default(0);
            $table->dateTime('joined_before')->nullable();
            $table->boolean('include_menu_button')->default(true);
            $table->timestamp('queued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('announcement_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->uuid('claim_token')->nullable();
            $table->timestamp('retry_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('telegram_message_id')->nullable();
            $table->unsignedSmallInteger('telegram_error_code')->nullable();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
            $table->index(['announcement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_deliveries');
        Schema::dropIfExists('announcements');
    }
};
