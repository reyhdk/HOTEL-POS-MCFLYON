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
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definisikan semua permission yang ada
        $permissions = [
            'pos', 'reports', 'master', 'settings',
            'view dashboard',
            'view rooms', 'create rooms', 'edit rooms', 'delete rooms',
            'view facilities', 'create facilities', 'edit facilities', 'delete facilities',
            'view guests', 'create guests', 'edit guests', 'delete guests',
            'view menus', 'create menus', 'edit menus', 'delete menus',
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
            'create pos_orders', 'view online_orders', 'manage payments', 'view folios',
            'manage service_requests',
            'view transaction_history', 'view checkout_history',
            'edit settings',
            'manage cleaning status',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }
        $this->command->info('Semua permission berhasil dibuat.');

        // 2. Tentukan permission untuk setiap role
        Role::findByName('admin', 'api')->syncPermissions($permissions);
        $this->command->info('Memberikan SEMUA permission ke role: admin');

        Role::findByName('receptionist', 'api')->syncPermissions([
            // Dashboard
            'view dashboard',
            // POS
            'pos', 'create pos_orders', 'manage payments', 'view folios',
            // Master Data (Hanya View, kecuali Guest)
            'master', 'view rooms', 'edit rooms', 'view facilities', 'view menus',
            'view guests', 'create guests', 'edit guests',
            // Layanan
            'manage service_requests', 'manage cleaning status',
            // Laporan
            'view checkout_history',
        ]);
        $this->command->info('Memberikan permission ke role: receptionist');

        Role::findByName('chef', 'api')->syncPermissions([
            'view dashboard',
            'pos', 'view online_orders',
            'master', 'view menus', 'create menus', 'edit menus',
        ]);
        $this->command->info('Memberikan permission ke role: chef');

        Role::findByName('cleaning-service', 'api')->syncPermissions([
            'view dashboard',
            'master',
            'view rooms', 'view facilities', 'view guests',
            'manage service_requests', 'manage cleaning status',
        ]);
        $this->command->info('Memberikan permission ke role: cleaning-service');

        Role::findByName('user', 'api')->syncPermissions([]);
        $this->command->info('Role "user" tidak diberikan permission admin.');
    }
}
