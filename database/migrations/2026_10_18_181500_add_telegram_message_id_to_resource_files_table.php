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
        Schema::table('resource_files', function (Blueprint $table) {
            $table->string('telegram_message_id', 100)->nullable()->after('telegram_file_unique_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resource_files', function (Blueprint $table) {
            $table->dropColumn('telegram_message_id');
        });
    }
};
