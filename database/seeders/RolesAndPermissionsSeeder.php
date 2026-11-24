<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Permissions
        $manageEvents = Permission::create(['name' => 'manage events']);

        // 2. Roles
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo($manageEvents);

        Role::create(['name' => 'user']);
    }
}
