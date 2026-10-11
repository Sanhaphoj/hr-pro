<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Front-camera photo taken at the punch, for roles that require
            // one. A path on the private `local` disk — never a public URL.
            $table->string('clock_in_photo')->nullable()->after('clock_in_off_site_reason');
            $table->string('clock_out_photo')->nullable()->after('clock_out_off_site_reason');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_photo', 'clock_out_photo']);
        });
    }
};
