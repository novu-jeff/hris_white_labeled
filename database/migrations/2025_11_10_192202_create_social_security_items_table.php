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
        if (Schema::hasTable('social_security_items')) {
            return;
        }

        Schema::create('social_security_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('social_security_id')->constrained('social_security')->onDelete('cascade');
            $table->string('bp_no')->nullable();
            $table->string('crn_no')->nullable();
            $table->date('effectivity_date')->nullable();
            $table->decimal('ps', 15, 2)->default(0);
            $table->decimal('gs', 15, 2)->default(0);
            $table->decimal('ec', 15, 2)->default(0);
            $table->decimal('consoloan', 15, 2)->default(0);
            $table->decimal('ecardplus', 15, 2)->default(0);
            $table->decimal('salary_loan', 15, 2)->default(0);
            $table->decimal('cash_adv', 15, 2)->default(0);
            $table->decimal('emrgy_loan', 15, 2)->default(0);
            $table->decimal('educ_loan', 15, 2)->default(0);
            $table->decimal('ela', 15, 2)->default(0);
            $table->decimal('sos', 15, 2)->default(0);
            $table->decimal('plreg', 15, 2)->default(0);
            $table->decimal('plopt', 15, 2)->default(0);
            $table->decimal('rel', 15, 2)->default(0);
            $table->decimal('lch_dcs', 15, 2)->default(0);
            $table->decimal('stock_purchase', 15, 2)->default(0);
            $table->decimal('opt_life', 15, 2)->default(0);
            $table->decimal('ceap', 15, 2)->default(0);
            $table->decimal('edu_child', 15, 2)->default(0);
            $table->decimal('genesis', 15, 2)->default(0);
            $table->decimal('genplus', 15, 2)->default(0);
            $table->decimal('genflexi', 15, 2)->default(0);
            $table->decimal('genspcl', 15, 2)->default(0);
            $table->decimal('help', 15, 2)->default(0);
            $table->decimal('gfal', 15, 2)->default(0);
            $table->decimal('mpl', 15, 2)->default(0);
            $table->decimal('cpl', 15, 2)->default(0);
            $table->decimal('gel', 15, 2)->default(0);
            $table->decimal('mpl_lite', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_security_items');
    }
};
