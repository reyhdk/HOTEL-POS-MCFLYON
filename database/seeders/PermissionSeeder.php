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

        // Izin Induk/Grup untuk Menu Utama (agar menu accordion bisa tampil)
        $groupPermissions = [
            'pos',
            'reports',
            'master',
            'settings',
        ];

        // Izin untuk Halaman Dashboard
        $dashboardPermissions = [
            'view dashboard',
        ];

        // Izin untuk Fitur Master Data
        $masterPermissions = [
            'view rooms', 'create rooms', 'edit rooms', 'delete rooms',
            'view facilities', 'create facilities', 'edit facilities', 'delete facilities',
            'view guests', 'create guests', 'edit guests', 'delete guests',
            'view menus', 'create menus', 'edit menus', 'delete menus',
            'view users', 'create users', 'edit users', 'delete users',
            'view roles', 'create roles', 'edit roles', 'delete roles',
        ];

        // Izin untuk Fitur Point of Sale (POS)
        $posPermissions = [
            'create pos_orders',
            'view online_orders',
            'manage payments',
            'view folios',
        ];

        $serviceRequestPermissions = [
            'manage service_requests', // Izin untuk mengelola permintaan layanan
        ];


        // Izin untuk Fitur Laporan
        $reportPermissions = [
            'view transaction_history',
            'view checkout_history', // Izin baru untuk melihat riwayat checkout

        ];

        // Izin untuk Fitur Pengaturan
        $settingPermissions = [
            'edit settings',
        ];

        // Gabungkan semua permission menjadi satu array besar
        $allPermissions = array_merge(
            $groupPermissions,
            $dashboardPermissions,
            $masterPermissions,
            $posPermissions,
            $reportPermissions,
            $serviceRequestPermissions,
            $settingPermissions
        );

        // Buat semua permission yang telah didefinisikan ke dalam database
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }

        $this->command->info('Semua permission berhasil dibuat.');

        // 2. Tentukan permission spesifik untuk setiap role
        $permissionsByRole = [
            // Admin mendapatkan semua permission yang ada
            'admin' => $allPermissions,

            'receptionist' => [
                'view dashboard',
                'pos', 'create pos_orders', 'manage payments', 'view folios',
                'master', 'view rooms', 'view guests', 'create guests', 'edit guests',
                'manage service_requests',
                'view checkout_history',

            ],

            'chef' => [
                'view dashboard',
                'pos', 'view online_orders',
                'master', 'view menus', 'create menus', 'edit menus',
            ],

            'cleaning-service' => [
                'view dashboard',
                'master',
                'view rooms',
                'manage service_requests',
            ],

            // Role 'user' (tamu) tidak memiliki permission untuk panel admin
            'user' => []
        ];

        // 3. Berikan permission tersebut ke setiap role yang sesuai
        foreach ($permissionsByRole as $roleName => $permissionNames) {
            $role = Role::whereName($roleName)->first();
            if ($role) {
                $role->syncPermissions($permissionNames);
                $this->command->info("Memberikan permission ke role: {$roleName}");
            }
        }
    }
}
