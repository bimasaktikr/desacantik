<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sls;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlsPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_sls');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sls $sls): bool
    {
        return $user->can('view_sls');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_sls');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sls $sls): bool
    {
        return $user->can('update_sls');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sls $sls): bool
    {
        return $user->can('delete_sls');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_sls');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, Sls $sls): bool
    {
        return $user->can('force_delete_sls');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_sls');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, Sls $sls): bool
    {
        return $user->can('restore_sls');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_sls');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, Sls $sls): bool
    {
        return $user->can('replicate_sls');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_sls');
    }
}
