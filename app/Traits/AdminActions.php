<?php

namespace App\Traits;

/**
 * Admin Actions Only
 */
trait AdminActions
{
    public function before($user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }
}
