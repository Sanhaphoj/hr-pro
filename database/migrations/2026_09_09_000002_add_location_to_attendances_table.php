<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Where the employee stood when they punched in / out. Kept as a
            // snapshot rather than a lookup so an audited record stays true
            // even if the zone is later moved, resized or deleted.
            $table->foreignId('clock_in_location_id')->nullable()->after('clock_in')
                ->constrained('work_locations')->nullOnDelete();
            $table->decimal('clock_in_latitude', 10, 7)->nullable()->after('clock_in_location_id');
            $table->decimal('clock_in_longitude', 10, 7)->nullable()->after('clock_in_latitude');
            $table->unsignedInteger('clock_in_accuracy')->nullable()->after('clock_in_longitude');
            $table->unsignedInteger('clock_in_distance')->nullable()->after('clock_in_accuracy');

            $table->foreignId('clock_out_location_id')->nullable()->after('clock_out')
                ->constrained('work_locations')->nullOnDelete();
            $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_location_id');
            $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            $table->unsignedInteger('clock_out_accuracy')->nullable()->after('clock_out_longitude');
            $table->unsignedInteger('clock_out_distance')->nullable()->after('clock_out_accuracy');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('clock_in_location_id');
            $table->dropConstrainedForeignId('clock_out_location_id');
            $table->dropColumn([
                'clock_in_latitude', 'clock_in_longitude', 'clock_in_accuracy', 'clock_in_distance',
                'clock_out_latitude', 'clock_out_longitude', 'clock_out_accuracy', 'clock_out_distance',
            ]);
        });
    }
};
