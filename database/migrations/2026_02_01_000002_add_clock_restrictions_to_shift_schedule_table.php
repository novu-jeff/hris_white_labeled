<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->boolean('allow_anytime_clockin')->default(false)->after('is_breaktime_required');
            $table->boolean('allow_anytime_clockout')->default(false)->after('allow_anytime_clockin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shift_schedule', function (Blueprint $table) {
            $table->dropColumn(['allow_anytime_clockin', 'allow_anytime_clockout']);
        });
    }
};
