<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->string('address')->nullable();

            // WGS-84 decimal degrees. 7 decimals ≈ 1 cm resolution, which is
            // far finer than any consumer GPS fix — no rounding loss here.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // Geofence radius around the point above, in metres.
            $table->unsignedInteger('radius_meters')->default(150);

            $table->boolean('is_active')->default(true);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_locations');
    }
};
