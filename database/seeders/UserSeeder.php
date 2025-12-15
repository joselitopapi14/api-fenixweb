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

        User::firstOrCreate(
            ['email' => 'ronalabn@gmail.com'],
            [
                'name' => 'Ronal Blanquicett',
                'password' => Hash::make('Ronal2025*')
            ]
        );

        User::firstOrCreate(
            ['email' => 'enriquejo2002@gmail.com'],
            [
                'name' => 'Jose Otero',
                'password' => Hash::make('joselito1234')
            ]
        );

        // Asignar rol de admin al primer usuario y al tercero (Jose Otero)
        $adminRole = Role::where('name', 'role.admin')->first();
        if ($adminRole) {
            if ($adminUser) {
                $adminUser->assignRole($adminRole);
            }
            
            $joseUser = User::where('email', 'enriquejo2002@gmail.com')->first();
            if ($joseUser) {
                $joseUser->assignRole($adminRole);
            }
        }
    }
}

