<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LocalDefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        // Safety: do not create default users outside local unless explicitly configured.
        if (!app()->isLocal()) {
            return;
        }

        $email = trim((string) env('DEFAULT_ADMIN_EMAIL', ''));
        $password = (string) env('DEFAULT_ADMIN_PASSWORD', '');
        $name = trim((string) env('DEFAULT_ADMIN_NAME', 'Admin'));

        if ($email === '' || $password === '') {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password)]
        );

        // Assign admin role if Spatie is installed/configured.
        if (method_exists($user, 'assignRole')) {
            if (!$user->hasRole('role.admin')) {
                $user->assignRole('role.admin');
            }
        }
    }
}
