<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Option to compute deduction manually (HR inputs per employee) or automatic (system calculates where supported).
     */
    public function up(): void
    {
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->string('computation_mode', 20)->default('manual')->after('maximum_amount')
                ->comment('manual = HR inputs per employee; automatic = system calculates where supported');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->dropColumn('computation_mode');
        });
    }
};
