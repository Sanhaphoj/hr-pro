<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Optional explanations captured at the moment of the punch. They
            // are prompted for, never required — an employee who declines
            // still clocks in or out.
            $table->string('late_reason')->nullable()->after('status');

            // Recorded at clock-out rather than derived later, so history stays
            // true if the configured end of the working day ever changes.
            $table->boolean('left_early')->default(false)->after('late_reason');
            $table->string('early_leave_reason')->nullable()->after('left_early');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_reason', 'left_early', 'early_leave_reason']);
        });
    }
};
