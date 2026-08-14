<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalGroupApprover extends Model
{
    use HasFactory;

    protected $table = 'approval_group_approvers';

    protected $fillable = [
        'approval_group_id',
        'approver_id',
        'approver_type',
        'approver_name',
        'approver_email',
        'order_index',
    ];

    public function group()
    {
        return $this->belongsTo(ApprovalGroup::class, 'approval_group_id');
    }
}
