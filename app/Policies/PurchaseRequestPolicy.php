<?php

namespace App\Policies;

use App\Models\User;
use App\Modules\Purchase\Models\PurchaseRequest;

class PurchaseRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('purchase-requests.view_any') || $user->can('purchase-requests.view');
    }

    public function view(User $user, PurchaseRequest $model): bool
    {
        return $user->can('purchase-requests.view');
    }

    public function create(User $user): bool
    {
        return $user->can('purchase-requests.create');
    }

    public function update(User $user, PurchaseRequest $model): bool
    {
        return $user->can('purchase-requests.update');
    }

    public function delete(User $user, PurchaseRequest $model): bool
    {
        return $user->can('purchase-requests.delete');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('purchase-requests.delete_any') || $user->can('purchase-requests.delete');
    }

    public function restore(User $user, PurchaseRequest $model): bool
    {
        return $user->can('purchase-requests.restore');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('purchase-requests.restore_any') || $user->can('purchase-requests.restore');
    }

    public function forceDelete(User $user, PurchaseRequest $model): bool
    {
        return $user->can('purchase-requests.force_delete');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('purchase-requests.force_delete_any') || $user->can('purchase-requests.force_delete');
    }
}
