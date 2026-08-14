<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $availableBranches = Helpers::getEntityBranches();

        if(count($availableBranches) > 0){
            $builder->whereIn('id', $availableBranches);
        }
        if(env('default_entity_id')){
            $builder->where('entityid', session()->get('contractSessionEntity') ?? env('default_entity_id'));
        }
    }
}
