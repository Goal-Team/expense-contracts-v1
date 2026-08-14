<?php

namespace App\Models\Scopes;
use App\Helpers\Helpers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserBranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $availableBranches = Helpers::getEntityBranches();
        if(count($availableBranches) > 0){
            $builder->where(function($query) use($availableBranches) {
                        $numItems = count($availableBranches);
                        $i = 0;   
                        $queryStringFinal = "";
                        foreach($availableBranches as $avlBr) {
                            $orCondition = "OR ";
                            if(++$i === $numItems) {
                                $orCondition = "";
                            }
                            $queryStringFinal .= "branchhead IN ($avlBr) $orCondition";
                        };
				  $queryStringFinal .= " OR branchhead = ''";
                        $query->whereRaw($queryStringFinal);
                    });           
        }
        
        $builder->where(decrypt_datas('AccessScope', 'AddUsers'), 'LIKE', '%Contracts%')->where(decrypt_datas('Status', 'AddUsers'), 'Active');
            $builder->where('Customer', session()->get('contractSessionEntity'));
    }
}