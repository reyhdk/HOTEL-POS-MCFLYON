<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisikan semua permission yang ada
        $permissions = [
            // Izin Grup Menu
            'pos', 'reports', 'master', 'settings',

            // Izin Dashboard
            'view dashboard',

            // Izin Master Data
            'view rooms', 'create rooms', 'edit rooms', 'delete rooms',
            'view facilities', 'create facilities', 'edit facilities', 'delete facilities',
            'view guests', 'create guests', 'edit guests', 'delete guests',
            'view menus', 'create menus', 'edit menus', 'delete menus',
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',

            // Izin POS
            'create pos_orders', 'view online_orders', 'manage payments', 'view folios',

            // Izin Layanan Kamar
            'manage service_requests',

            // Izin Laporan
            'view transaction_history', 'view checkout_history',

            // Izin Pengaturan
            'edit settings',
        ];

        // Buat semua permission ke dalam database
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }
        $this->command->info('Semua permission berhasil dibuat.');

        // 2. Tentukan permission untuk setiap role
        Role::findByName('admin', 'api')->syncPermissions($permissions);
        $this->command->info('Memberikan SEMUA permission ke role: admin');

        Role::findByName('receptionist', 'api')->syncPermissions([
            'view dashboard',
            'pos', 'create pos_orders', 'manage payments', 'view folios',
            'master', 'view rooms', 'view guests', 'create guests', 'edit guests', 'view facilities',
            'manage service_requests',
            'view checkout_history',
        ]);
        $this->command->info('Memberikan permission ke role: receptionist');

        Role::findByName('chef', 'api')->syncPermissions([
            'view dashboard',
            'pos', 'view online_orders',
            'master', 'view menus', 'create menus', 'edit menus',
        ]);
        $this->command->info('Memberikan permission ke role: chef');

        // --- PENYESUAIAN UNTUK CLEANING SERVICE ---
        Role::findByName('cleaning-service', 'api')->syncPermissions([
            'view dashboard',
            'master',
            'view rooms',
            'view facilities',
            'manage service_requests',
            'view guests',
            'edit rooms',
        ]);
        $this->command->info('Memberikan permission ke role: cleaning-service');

        // Role 'user' (tamu) tidak perlu permission admin
        Role::findByName('user', 'api')->syncPermissions([]);
        $this->command->info('Role "user" tidak diberikan permission admin.');
    }
}
