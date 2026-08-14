<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalGroupSet extends Model
{
    use HasFactory;

    protected $table = 'approval_group_sets';

    protected $fillable = [
        'financial_limit_id',
        'approval_type',
    ];

    public function financialLimit()
    {
        return $this->belongsTo(FinancialLimit::class, 'financial_limit_id');
    }

    public function groups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->orderBy('parent_type')->orderBy('order_index');
    }

    public function reviewGroups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->where('parent_type', 'review')->orderBy('order_index');
    }

    public function negotiationGroups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->where('parent_type', 'negotiation')->orderBy('order_index');
    }

    public function finalizationGroups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->where('parent_type', 'finalization')->orderBy('order_index');
    }

    public function approvalGroups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->where('parent_type', 'approval')->orderBy('order_index');
    }

    public function signatoryGroups()
    {
        return $this->hasMany(ApprovalGroup::class, 'approval_group_set_id')->where('parent_type', 'signatory')->orderBy('order_index');
    }
}
