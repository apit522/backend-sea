<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DevicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Semua user yang login bisa melihat daftar device mereka sendiri
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * INI YANG AKAN DIPANGGIL UNTUK show, getData, getRawData, dll.
     */
    public function view(User $user, Device $device): bool
    {
        // Hanya izinkan jika ID user sama dengan user_id pada device
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Semua user yang login bisa membuat device
        return true;
    }

    /**
     * Determine whether the user can update the model.
     * INI YANG AKAN DIPANGGIL UNTUK update.
     */
    public function update(User $user, Device $device): bool
    {
        // Hanya izinkan jika ID user sama dengan user_id pada device
        return $user->id === $device->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Device $device): bool
    {
        // Hanya izinkan jika ID user sama dengan user_id pada device
        return $user->id === $device->user_id;
    }
}