<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalGroup extends Model
{
    use HasFactory;

    protected $table = 'approval_groups';

    protected $fillable = [
        'approval_group_set_id',
        'parent_type',
        'role',
        'approval_type',
        'auto_next_enabled',
        'dynamic_approver_enabled',
        'order_index',
    ];

    public function groupSet()
    {
        return $this->belongsTo(ApprovalGroupSet::class, 'approval_group_set_id');
    }

    public function approvers()
    {
        return $this->hasMany(ApprovalGroupApprover::class, 'approval_group_id')->orderBy('order_index');
    }
}
