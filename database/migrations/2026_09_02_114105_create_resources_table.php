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
        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('resource_type_id')->constrained('resource_types')->restrictOnDelete();
            $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->date('exam_date')->nullable();
            $table->string('language', 10)->nullable();
            $table->boolean('has_solutions')->default(false);
            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'archived',
            ])->default('pending');
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['course_id', 'resource_type_id', 'academic_year_id', 'status'], 'idx_resources_browse');
            $table->index('exam_date', 'idx_resources_exam_date');

            if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                $table->fullText(
                    ['title', 'description'],
                    'ft_resources_text'
                );
            }

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resources');
    }
};
