<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialLimit extends Model
{
    use HasFactory;

    protected $table = 'financial_limit';

    protected $fillable = ['approval_name','location','department','category','contract_type','lower_limit','upper_limit','approver','sameAsAll','approval_type', 'approval_status','approval_required_users','approval_signatory_owner',
    'approval_required_users_edit',
    'approval_required_users_legacy',
    'approval_required_users_legacy_edit',
    'approval_required_users_renewed',
    'approval_required_users_addendum',
    'approval_required_users_terminate',
    'rule_builder_data',
    'approval_group_set_id'
    ];

    public function approvalGroupSets()
    {
        return $this->hasMany(ApprovalGroupSet::class, 'financial_limit_id');
    }

    public function defaultApprovalGroupSet()
    {
        return $this->belongsTo(ApprovalGroupSet::class, 'approval_group_set_id');
    }
}