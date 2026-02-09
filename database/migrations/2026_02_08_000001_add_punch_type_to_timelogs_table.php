<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a column to identify if the punch is clock in or clock out (2 values only).
     */
    public function up(): void
    {
        if (config('app.external_timelogs') !== false && config('app.external_timelogs') !== 'false') {
            return;
        }

        Schema::table('timelogs', function (Blueprint $table) {
            $table->string('punch_type', 20)
                ->nullable()
                ->after('status')
                ->comment('clock_in or clock_out');
        });

        // Backfill: status 0 = in -> clock_in, status 1 = out -> clock_out
        DB::table('timelogs')->where('status', 0)->update(['punch_type' => 'clock_in']);
        DB::table('timelogs')->where('status', 1)->update(['punch_type' => 'clock_out']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('app.external_timelogs') !== false && config('app.external_timelogs') !== 'false') {
            return;
        }

        Schema::table('timelogs', function (Blueprint $table) {
            $table->dropColumn('punch_type');
        });
    }
};
