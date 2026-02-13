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
        Schema::create('offset_credits', function (Blueprint $table) {
            $table->id();
            $table->string('employee_no')->unique();
            $table->decimal('credits', 10, 2)->default(0);
            $table->string('as_of')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offset_credits');
    }
};
