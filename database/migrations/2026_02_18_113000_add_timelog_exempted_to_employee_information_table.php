<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_information', 'is_timelog_exempted')) {
                $table->boolean('is_timelog_exempted')
                    ->default(false)
                    ->after('has_salary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            if (Schema::hasColumn('employee_information', 'is_timelog_exempted')) {
                $table->dropColumn('is_timelog_exempted');
            }
        });
    }
};
