<?php

namespace Database\Seeders;

use App\Models\Departments;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $product = config('app.product');

        if($product == 'government') {
            $data = [
                ['code' => 'P1', 'name' => '1', 'description' => 'GPH - MILF Peace Process'],
                ['code' => 'P2', 'name' => '2', 'description' => 'GPH - MNLF Peace Process'],
                ['code' => 'P3', 'name' => '3', 'description' => 'Localized Peace Engagement'],
                ['code' => 'P4', 'name' => '4', 'description' => 'GPH - RPM-P / RPA / ABB / CBA-CPLA Peace Process'],
                ['code' => 'P5', 'name' => '5', 'description' => 'Social Healing and Peacebuilding'],
                ['code' => 'P6', 'name' => '6', 'description' => 'PAyapa at MAsaganang PamayaNAn (PAMANA) Program'],
                ['code' => 'P7', 'name' => '7', 'description' => 'Internal Cooperation and Partnership'],
                ['code' => 'P8', 'name' => '8', 'description' => 'Human Capital / Organization Capital / Finance and Resources / Strategic Communications'],
                ['code' => 'EO', 'name' => 'Executive Offices', 'description' => 'Executive Office']
            ];
        } else {
            $data = [];
        }

        foreach ($data as $data) {
            Departments::updateOrCreate(
                ['code' => $data['code'], 'name' => $data['name']],
                $data
            );
        }
    }
}
