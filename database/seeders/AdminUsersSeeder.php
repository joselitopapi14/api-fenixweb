<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUsersSeeder extends Seeder
{
    /**
     * Seed admin users with hardcoded credentials.
     * 
     * IMPORTANT: These are default admin users for initial setup.
     * Change passwords after first login in production.
     */
    public function run(): void
    {
        $adminUsers = [
            [
                'name' => 'Ronal Blanquicett',
                'email' => 'ronalabn@gmail.com',
                'password' => 'Ronal2024!', // Change after first login
            ],
            [
                'name' => 'Gabriel Galeano Guerra',
                'email' => 'ggaleanoguerra@gmail.com',
                'password' => 'Gabriel2024!', // Change after first login
            ],
            [
                'name' => 'Jose',
                'email' => 'jose@fenixweb.com',
                'password' => 'Jose2024!', // Change after first login
            ],
        ];

        foreach ($adminUsers as $userData) {
            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'email_verified_at' => now(),
                ]
            );

            // Assign admin role if Spatie Permission is installed
            if (method_exists($user, 'assignRole')) {
                if (!$user->hasRole('role.admin')) {
                    $user->assignRole('role.admin');
                }
            }

            $this->command?->info("✓ Admin user created/updated: {$userData['email']}");
        }

        $this->command?->warn('⚠️  SECURITY: Change default passwords after first login!');
    }
}
