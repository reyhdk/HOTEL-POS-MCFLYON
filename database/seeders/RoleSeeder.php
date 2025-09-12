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

        // PERBAIKAN: Tambahkan 'full_name' saat membuat role
        Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['full_name' => 'Administrator'] // Nilai untuk full_name
        );

        Role::firstOrCreate(
            ['name' => 'user', 'guard_name' => 'api'],
            ['full_name' => 'User'] // Nilai untuk full_name
        );
    }
}