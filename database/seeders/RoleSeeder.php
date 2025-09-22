<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Role Inti
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['full_name' => 'Administrator']
        );
        Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'api'],
            ['full_name' => 'User']
        );

        // [DIPERBAIKI] Role Staf Hotel Ditambahkan di Sini
        Role::firstOrCreate(
            ['name' => 'receptionist', 'guard_name' => 'api'],
            ['full_name' => 'Receptionist']
        );
        Role::firstOrCreate(
            ['name' => 'chef', 'guard_name' => 'api'],
            ['full_name' => 'Chef']
        );
        Role::firstOrCreate(
            ['name' => 'cleaning-service', 'guard_name' => 'api'],
            ['full_name' => 'Cleaning Service']
        );
    }
}
