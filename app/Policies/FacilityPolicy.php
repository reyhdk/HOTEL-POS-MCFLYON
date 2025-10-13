<?php

namespace App\Policies;

use App\Models\Facility;
use App\Models\User; 
use Illuminate\Auth\Access\Response;

class FacilityPolicy
{
    /**
     * Tentukan apakah pengguna dapat melihat DAFTAR fasilitas.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view facilities');
    }

    /**
     * Tentukan apakah pengguna dapat melihat SATU fasilitas.
     */
    public function view(User $user, Facility $facility): bool
    {
        return $user->can('view facilities');
    }

    /**
     * Tentukan apakah pengguna dapat MEMBUAT fasilitas baru.
     */
    public function create(User $user): bool
    {
        return $user->can('create facilities');
    }

    /**
     * Tentukan apakah pengguna dapat MENGEDIT fasilitas.
     */
    public function update(User $user, Facility $facility): bool
    {
        return $user->can('edit facilities');
    }

    /**
     * Tentukan apakah pengguna dapat MENGHAPUS fasilitas.
     */
    public function delete(User $user, Facility $facility): bool
    {
        return $user->can('delete facilities');
    }
}
