<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = User::firstOrCreate(
            ['email' => 'ggaleanoguerra@gmail.com'],
            [
                'name' => 'Gabriel Galeano Guerra',
                'password' => Hash::make('Thegamechanger1*')
            ]
        );

        $ronalUser = User::firstOrCreate(
            ['email' => 'ronalabn@gmail.com'],
            [
                'name' => 'Ronal Blanquicett',
                'password' => Hash::make('Ronal2025*')
            ]
        );

        $joseUser = User::firstOrCreate(
            ['email' => 'enriquejo2002@gmail.com'],
            [
                'name' => 'Jose Otero',
                'password' => Hash::make('joselito1234')
            ]
        );

        // Asignar rol de admin a los 3 usuarios
        $adminRole = Role::where('name', 'role.admin')->first();
        if ($adminRole) {
            if ($adminUser && !$adminUser->hasRole($adminRole)) {
                $adminUser->assignRole($adminRole);
            }
            
            if ($ronalUser && !$ronalUser->hasRole($adminRole)) {
                $ronalUser->assignRole($adminRole);
            }
            
            if ($joseUser && !$joseUser->hasRole($adminRole)) {
                $joseUser->assignRole($adminRole);
            }
        }
    }
}

