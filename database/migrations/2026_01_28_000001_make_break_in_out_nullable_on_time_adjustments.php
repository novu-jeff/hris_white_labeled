<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Employee time adjustment has no lunch in/out; break_in and break_out are optional.
     */
    public function up(): void
    {
        Schema::table('employee_time_adjustments', function (Blueprint $table) {
            $table->string('break_out')->nullable()->change();
            $table->string('break_in')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_time_adjustments', function (Blueprint $table) {
            $table->string('break_out')->nullable(false)->change();
            $table->string('break_in')->nullable(false)->change();
        });
    }
};
