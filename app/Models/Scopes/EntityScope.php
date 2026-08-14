<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EntityScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {

        if(env('default_entity_id')){
            $builder->where('id', session()->get('contractSessionEntity'));
        }
    }
}
