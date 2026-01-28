<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('employment_types')->where('name', 'Interns')->exists();
        if ($exists) {
            return;
        }
        $id = DB::table('employment_types')->insertGetId([
            'name' => 'Interns',
            'code' => 'INT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('employment_type_settings')->insert([
            'employment_type_id' => $id,
            'is_salary' => 0,
            'is_ot_pay' => 0,
            'is_clothing_allowance' => 0,
            'is_mid_year' => 0,
            'is_year_end' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $id = DB::table('employment_types')->where('name', 'Interns')->value('id');
        if ($id) {
            DB::table('employment_type_settings')->where('employment_type_id', $id)->delete();
            DB::table('employment_types')->where('id', $id)->delete();
        }
    }
};
