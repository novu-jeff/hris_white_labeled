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
        if (Schema::hasTable('employee_earnings')) {
            return;
        }

        Schema::create('employee_earnings', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no'); // Employee reference
            $table->unsignedBigInteger('earning_id'); // Foreign key to other_earnings
            $table->enum('amount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('first_term', 10, 2)->nullable();
            $table->decimal('second_term', 10, 2)->nullable();
            $table->timestamps();

            // Optional foreign key constraints (if related tables exist)
            $table->foreign('earning_id')
                ->references('id')
                ->on('other_earnings')
                ->onDelete('cascade');

            // If employee_personal has employee_no as unique or primary
            // Uncomment if you want a foreign key link
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
        Schema::dropIfExists('employee_earnings');
    }
};
