<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Bank account numbers are identifiers, not math values.
        DB::statement('ALTER TABLE payroll_salary_items MODIFY bank_account VARCHAR(50) NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE payroll_salary_items MODIFY bank_account DECIMAL(12,2) NULL');
    }
};
