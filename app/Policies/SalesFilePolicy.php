<?php

namespace App\Policies;

use App\Models\SalesFile;
use App\Models\User;

class SalesFilePolicy
{
    public function view(User $user, SalesFile $salesFile): bool
    {
        if ($user->can('sales.view_all') || $user->operatorAllows('view_all_sales')) {
            return true;
        }

        return $user->can('sales.view_own') && $salesFile->user_id === $user->id;
    }

    public function update(User $user, SalesFile $salesFile): bool
    {
        return $this->view($user, $salesFile);
    }

    public function delete(User $user, SalesFile $salesFile): bool
    {
        return $user->can('settings.manage') || $salesFile->user_id === $user->id;
    }
}