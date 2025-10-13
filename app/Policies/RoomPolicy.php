<?php

namespace App\Policies;

use App\Models\Room;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RoomPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat DAFTAR kamar.
     * Dijalankan oleh method index().
     */
    public function viewAny(User $user): bool
    {
        // Izinkan jika user memiliki permission 'view rooms'
        return $user->can('view rooms');
    }

    /**
     * Tentukan apakah pengguna dapat melihat SATU kamar.
     * Dijalankan oleh method show().
     */
    public function view(User $user, Room $room): bool
    {
        // Izinkan jika user memiliki permission 'view rooms'
        return $user->can('view rooms');
    }

    /**
     * Tentukan apakah pengguna dapat MEMBUAT kamar baru.
     * Dijalankan oleh method store().
     */
    public function create(User $user): bool
    {
        return $user->can('create rooms');
    }

    /**
     * Tentukan apakah pengguna dapat MENGEDIT kamar.
     * Dijalankan oleh method update().
     */
    public function update(User $user, Room $room): bool
    {
        return $user->can('edit rooms');
    }

    /**
     * Tentukan apakah pengguna dapat MENGHAPUS kamar.
     * Dijalankan oleh method destroy().
     */
    public function delete(User $user, Room $room): bool
    {
        return $user->can('delete rooms');
    }
}
