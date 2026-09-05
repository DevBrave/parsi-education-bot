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
        Schema::create('professor_resource', function (Blueprint $table) {
            $table->foreignId('professor_id')->constrained('professors')->restrictOnDelete();
            $table->foreignId('resource_id')->constrained('resources')->cascadeOnDelete();

            $table->primary(['professor_id', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('professor_resource');
    }
};
    