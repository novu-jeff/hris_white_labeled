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
        Schema::table('employee_information', function (Blueprint $table) {
            $table->decimal('allowance', 12, 2)->default(0)->nullable()->after('salary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_information', function (Blueprint $table) {
            $table->dropColumn(['allowance']);
        });
    }
};
