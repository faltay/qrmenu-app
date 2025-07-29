<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Restaurant management
            'manage restaurants',
            'view own restaurant',
            'edit own restaurant',
            
            // Menu management
            'manage menu items',
            'view menu items',
            'edit menu items',
            
            // User management
            'manage users',
            'view users',
            'edit users',
            
            // Reports
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles
        $superAdmin = Role::create(['name' => 'super-admin']);
        $admin = Role::create(['name' => 'admin']);
        $restaurantOwner = Role::create(['name' => 'restaurant-owner']);
        $manager = Role::create(['name' => 'manager']);

        // Assign permissions to roles
        $superAdmin->givePermissionTo(Permission::all());
        $admin->givePermissionTo(['manage restaurants', 'manage users', 'view reports']);
        $restaurantOwner->givePermissionTo(['view own restaurant', 'edit own restaurant', 'manage menu items', 'manage users']);
        $manager->givePermissionTo(['view menu items', 'edit menu items', 'view reports']);
    }
}