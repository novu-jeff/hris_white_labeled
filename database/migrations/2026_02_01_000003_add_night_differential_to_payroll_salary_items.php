<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (config('app.product') !== 'private') {
            return;
        }

        if (Schema::hasColumn('payroll_salary_items', 'night_differential')) {
            return;
        }
        Schema::table('payroll_salary_items', function (Blueprint $table) {
            $table->decimal('night_differential', 12, 2)->default(0)->after('holiday_pay');
        });
    }

    public function down(): void
    {
        if (config('app.product') !== 'private') {
            return;
        }

        Schema::table('payroll_salary_items', function (Blueprint $table) {
            $table->dropColumn('night_differential');
        });
    }
};
