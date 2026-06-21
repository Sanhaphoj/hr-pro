<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_posting_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('stage')->default('applied'); // applied|screening|interview|offer|hired|rejected
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['job_posting_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
