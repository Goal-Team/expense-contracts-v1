<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;
use App\Models\AddUsers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PartiesRoleBasedScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        
        // Respect admin setting: only apply role-based constraints when enabled
        if (! (bool) admin_setting('enable_role_based_data', false)) {
            return;
        }

        if(session()->get('contractSessionUserRole') == 'User'){
            $builder->where('created_by', session()->get('contractUserId'));
        }
        
        if(session()->get('contractSessionUserRole') == 'Marketing Manager'){
            $users = AddUsers::select('id')
                        ->where(decrypt_datas('Manager', 'AddUsers'), session()->get('contractSessionUser'))
                        ->pluck('id');
            $users[] = session()->get('contractUserId'); 
            $builder->whereIn('created_by', $users);
        }        
    }
}
