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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('telegram_user_id')->nullable()->change();
            $table->string('email')->nullable()->unique()->after('username');
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('is_blocked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email', 'password', 'remember_token']);
            $table->unsignedBigInteger('telegram_user_id')->nullable(false)->change();
        });
    }
};
