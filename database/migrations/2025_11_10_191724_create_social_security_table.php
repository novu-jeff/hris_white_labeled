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
        if (Schema::hasTable('social_security')) {
            return;
        }

        Schema::create('social_security', function (Blueprint $table) {
            $table->id();
            $table->string('remitting_agency');
            $table->string('office_code');
            $table->string('billing_month'); // you can also use date or integer depending on your format
            $table->date('date_uploaded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_security');
    }
};

