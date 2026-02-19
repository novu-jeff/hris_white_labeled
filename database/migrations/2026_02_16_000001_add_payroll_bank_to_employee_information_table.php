<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->string('payroll_bank', 128)->nullable()->after('payroll_account_number');
            $table->string('payroll_bank_other', 255)->nullable()->after('payroll_bank');
        });
    }

    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->dropColumn(['payroll_bank', 'payroll_bank_other']);
        });
    }
};
