<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Scope a query to only include records owned by the current user, 
     * unless the user is an administrator.
     */
    protected function scopeOwnedRecords($query)
    {
        $user = auth()->user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        $model = $query->getModel();

        // Use visibleTo scope if available (e.g. on Customer model)
        if (method_exists($model, 'scopeVisibleTo')) {
            return $query->visibleTo($user);
        }

        // For other models, if they use the OwnedByUser trait, 
        // the global scope is already applied automatically.
        return $query;
    }
}
