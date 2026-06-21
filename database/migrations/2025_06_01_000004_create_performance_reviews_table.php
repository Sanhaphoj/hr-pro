<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('period'); // e.g. 2026-H1
            $table->unsignedTinyInteger('score')->nullable(); // 1..5
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->string('status')->default('draft'); // draft | submitted
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
