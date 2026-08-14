<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserContractScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where(decrypt_datas('AccessScope', 'AddUsers'), 'LIKE', '%Contracts%')->where(decrypt_datas('Status', 'AddUsers'), 'Active');
        if(env('default_entity_id')){
            $builder->where('Customer', session()->get('contractSessionEntity') ?? env('default_entity_id'));
        }        
    }
}
