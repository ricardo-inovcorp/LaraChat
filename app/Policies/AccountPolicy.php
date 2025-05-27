<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Account $account)
    {
        return $account->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Account $account)
    {
        return $account->users()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Account $account)
    {
        return $account->users()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Account $account): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Account $account): bool
    {
        return false;
    }

    public function invite(User $user, Account $account)
    {
        return $account->users()
            ->where('user_id', $user->id)
            ->where('role', 'admin')
            ->exists();
    }
}
