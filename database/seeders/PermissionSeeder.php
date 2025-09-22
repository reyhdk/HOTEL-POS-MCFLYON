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

        // 1. Definisikan semua permission yang ada, dikelompokkan berdasarkan fitur
        $masterPermissions = [
            'view rooms', 'create rooms', 'edit rooms', 'delete rooms',
            'view facilities', 'create facilities', 'edit facilities', 'delete facilities',
            'view guests', 'create guests', 'edit guests', 'delete guests',
            'view menus', 'create menus', 'edit menus', 'delete menus',
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
        ];

        $posPermissions = [
            'create pos_orders',    // Untuk kasir di resepsionis
            'view online_orders',   // Untuk Chef dan Admin
            'manage payments',      // Untuk resepsionis
            'view folios',          // Untuk resepsionis
        ];

        $reportPermissions = [
            'view transaction_history',
        ];

        $settingPermissions = [
            'edit settings',
        ];

        // Gabungkan semua permission menjadi satu array
        $allPermissions = array_merge(
            $masterPermissions,
            $posPermissions,
            $reportPermissions,
            $settingPermissions
        );

        // Buat semua permission yang telah didefinisikan
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }

        $this->command->info('Semua permission berhasil dibuat.');

        // 2. Tentukan permission untuk setiap role
        $permissionsByRole = [
            'admin' => $allPermissions, // Admin mendapatkan semua permission

            'receptionist' => [
                'view rooms',
                'view guests', 'create guests', 'edit guests',
                'create pos_orders',
                'manage payments',
                'view folios',
            ],

            'chef' => [
                'view online_orders',
                'view menus', 'create menus', 'edit menus',
            ],

            'cleaning-service' => [
                'view rooms', // Hanya bisa melihat status kamar
            ],

            'user' => [
                // Role 'user' (tamu) biasanya tidak memiliki permission di admin panel
            ]
        ];

        // 3. Berikan permission ke setiap role
        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $role = Role::whereName($roleName)->first();
            if ($role) {
                // Gunakan syncPermissions untuk cara yang lebih bersih dan aman
                $role->syncPermissions($permissionNames);
                $this->command->info("Memberikan permission ke role: {$roleName}");
            }
        }
    }
}
