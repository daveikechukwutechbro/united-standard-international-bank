<?php

namespace Database\Seeders;

use App\Domain\User\Values\UserRoles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            UserRoles::BUSINESS->value,
            UserRoles::PRIVATE->value,
            UserRoles::ADMIN->value,
            'super_admin', // Add super_admin role
        ];

        // Seed each role into the database
        foreach ($roles as $role) {
            // Create for both web and sanctum guards
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'sanctum']);
        }
    }
}
