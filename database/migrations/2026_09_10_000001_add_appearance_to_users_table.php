<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Appearance follows the account rather than the browser, so the
            // console looks the same on a desk machine and a phone.
            $table->string('theme', 20)->default('teal')->after('is_active');
            $table->string('color_scheme', 10)->default('light')->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['theme', 'color_scheme']);
        });
    }
};
