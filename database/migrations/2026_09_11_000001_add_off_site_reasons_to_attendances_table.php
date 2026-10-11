<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // A punch taken outside every approved zone is accepted, but only
            // with an explanation. Kept per punch, and apart from the late /
            // early reasons, because one punch can be both late and off-site.
            // Whether a punch was off-site stays derived from the stored
            // coordinates (position recorded, no zone matched).
            $table->string('clock_in_off_site_reason', 250)->nullable()->after('clock_in_distance');
            $table->string('clock_out_off_site_reason', 250)->nullable()->after('clock_out_distance');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['clock_in_off_site_reason', 'clock_out_off_site_reason']);
        });
    }
};
