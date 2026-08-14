<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DepartmentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $availableDepartments = Helpers::getEntityBranches('', 1);
        if(count($availableDepartments) > 0){
            $builder->whereIn('id', $availableDepartments);
        }
        $builder->where('applicable', 1);
        if(env('default_entity_id')){
            $builder->where('entityid', session()->get('contractSessionEntity'));
        }
    }
}
