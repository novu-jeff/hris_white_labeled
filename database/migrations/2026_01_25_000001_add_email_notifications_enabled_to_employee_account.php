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
        Schema::table('employee_account', function (Blueprint $table) {
            if (!Schema::hasColumn('employee_account', 'email_notifications_enabled')) {
                $table->boolean('email_notifications_enabled')
                    ->default(false)
                    ->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_account', function (Blueprint $table) {
            if (Schema::hasColumn('employee_account', 'email_notifications_enabled')) {
                $table->dropColumn('email_notifications_enabled');
            }
        });
    }
};

