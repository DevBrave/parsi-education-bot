<?php

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
        Schema::create('resource_files', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resource_id')
                ->constrained('resources')
                ->cascadeOnDelete();

            $table->string('original_filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);

            $table->string('drive_file_id', 255)->nullable();
            $table->string('telegram_file_id', 512)->nullable();
            $table->string('telegram_file_unique_id', 255)->nullable();

            $table->timestamps();

            $table->unique(
                'drive_file_id',
                'uq_resource_files_drive_file_id'
            );

            $table->unique(
                ['resource_id', 'sha256'],
                'uq_resource_files_resource_hash'
            );

            $table->index(
                'telegram_file_unique_id',
                'idx_resource_files_telegram_unique_id'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resource_files');
    }
};
