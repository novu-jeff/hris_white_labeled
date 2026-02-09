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
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->decimal('maximum_amount', 12, 2)->nullable()->after('amount_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('other_deductions', function (Blueprint $table) {
            $table->dropColumn('maximum_amount');
        });
    }
};
