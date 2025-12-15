<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
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
    }
}
