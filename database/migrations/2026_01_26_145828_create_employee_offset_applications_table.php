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
        Schema::create('employee_offset_applications', function (Blueprint $table) {
            $table->id();

            $table->string('employee_no');        
            $table->date('date_filed')->nullable();
            $table->date('offset_date_from')->nullable();
            $table->date('offset_date_to')->nullable();
            
            $table->string('purpose')->nullable();

            $table->string('requested_by')->nullable();
            $table->enum('status', [
                'approved',
                'disapproved',
                'pending',
                'cancelled'
            ])->default('pending');

            $table->longText('remarks')
                ->nullable();
        
            $table->foreignId('action_by_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->boolean('isDeleted')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_offset_applications');
    }
};
