<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_module_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_type_id')
                ->constrained('employment_types')
                ->onDelete('cascade');
            $table->string('module_key', 64);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['employment_type_id', 'module_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_module_settings');
    }
};
