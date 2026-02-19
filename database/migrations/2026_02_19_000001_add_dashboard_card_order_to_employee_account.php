<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_account', function (Blueprint $table) {
            $table->json('dashboard_card_order')->nullable()->after('login_attempts');
        });
    }

    public function down(): void
    {
        Schema::table('employee_account', function (Blueprint $table) {
            $table->dropColumn('dashboard_card_order');
        });
    }
};
