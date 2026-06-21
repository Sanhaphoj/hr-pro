<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('enrolled'); // enrolled | completed | cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['training_course_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_enrollments');
    }
};
