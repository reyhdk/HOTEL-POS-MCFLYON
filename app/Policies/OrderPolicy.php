<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat DAFTAR pesanan.
     */
    public function viewAny(User $user): bool
    {
        // Contoh: Izinkan jika user punya izin 'view online_orders'
        return $user->can('view online_orders');
    }

    /**
     * Tentukan apakah pengguna dapat melihat SATU pesanan.
     */
    public function view(User $user, Order $order): bool
    {
        // Izinkan jika user punya izin atau jika pesanan itu miliknya
        return $user->can('view online_orders') || $user->id === $order->user_id;
    }

    /**
     * [PALING PENTING] Tentukan apakah pengguna dapat MEMBUAT pesanan baru.
     */
    public function create(User $user): bool
    {
        // Izinkan jika user memiliki permission 'create pos_orders'
        return $user->can('create pos_orders');
    }

    /**
     * Tentukan apakah pengguna dapat MENGEDIT pesanan.
     */
    public function update(User $user, Order $order): bool
    {
        // Contoh: Izinkan jika user punya izin 'manage payments'
        return $user->can('manage payments');
    }

    /**
     * Tentukan apakah pengguna dapat MENGHAPUS pesanan.
     */
    public function delete(User $user, Order $order): bool
    {
        // Contoh: Izinkan jika user punya izin 'manage payments'
        return $user->can('manage payments');
    }
}
