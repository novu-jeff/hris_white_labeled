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
        if (Schema::hasTable('employee_deductions')) {
            return;
        }

        Schema::create('employee_deductions', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no'); // Links to employee_personal.employee_no
            $table->unsignedBigInteger('deduction_id'); // Links to other_deductions.id
            $table->decimal('amount', 10, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamps();

            // Optional foreign key constraints
            $table->foreign('deduction_id')
                ->references('id')
                ->on('other_deductions')
                ->onDelete('cascade');

            // If you have employee_personal table with employee_no column
            // Uncomment the following:
            // $table->foreign('employee_no')
            //     ->references('employee_no')
            //     ->on('employee_personal')
            //     ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_deductions');
    }
};
