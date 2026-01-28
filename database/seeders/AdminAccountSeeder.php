<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $product = config('app.product');

        if($product == 'government') {
            $admins = [
                [
                    'name' => 'Opapru Superadmin', 
                    'username' => 'opapru01',
                    'role' => 'superadmin',
                    'email' => 'opapru01@hris.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Opapru Admin', 
                    'username' => 'opapru02',
                    'role' => 'admin',
                    'email' => 'opapru02@hris.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Mike Gabriel Pascaran', 
                    'username' => 'mike01',
                    'role' => 'superadmin',
                    'email' => 'mike@novulutions.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Christina Francisco', 
                    'username' => 'christina01',
                    'role' => 'superadmin',
                    'email' => 'christina@novulutions.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Ronna', 
                    'username' => 'ronna01',
                    'role' => 'superadmin',
                    'email' => 'ronna@novulutions.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Micole Lapeña', 
                    'username' => 'micole01',
                    'role' => 'superadmin',
                    'email' => 'micole@novulutions.com', 
                    'password' => Hash::make('password')
                ],
                [
                    'name' => 'Lechler', 
                    'username' => 'lech01',
                    'role' => 'superadmin',
                    'email' => 'lechler@novulutions.com', 
                    'password' => Hash::make('password')
                ],
            ];
        } 

        $admins[] =  [
            'name' => 'Jeff De La Torre', 
            'username' => 'dev01',
            'role' => 'superadmin',
            'email' => 'jeff@novulutions.com', 
            'password' => Hash::make('password')
        ];
        

        foreach ($admins as $admin) {
            $user = User::updateOrCreate(
                [ 'email' => $admin['email']], 
                [
                    'name' => $admin['name'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'password' => $admin['password']
                ]
            );
            $user->assignRole($admin['role']);
        }
    }
}
