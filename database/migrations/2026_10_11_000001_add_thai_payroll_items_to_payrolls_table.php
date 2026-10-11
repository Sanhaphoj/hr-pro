<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Itemised pay: overtime on the earnings side; unpaid leave, social
 * security, provident fund and withholding tax on the deductions side.
 * `deductions` stays as their total so existing reports keep working.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('overtime_hours', 6, 2)->default(0)->after('allowances');
            $table->decimal('overtime_pay', 12, 2)->default(0)->after('overtime_hours');
            $table->decimal('unpaid_leave_deduction', 12, 2)->default(0)->after('overtime_pay');
            $table->decimal('social_security', 12, 2)->default(0)->after('unpaid_leave_deduction');
            $table->decimal('provident_fund', 12, 2)->default(0)->after('social_security');
            $table->decimal('withholding_tax', 12, 2)->default(0)->after('provident_fund');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['overtime_hours', 'overtime_pay', 'unpaid_leave_deduction', 'social_security', 'provident_fund', 'withholding_tax']);
        });
    }
};
