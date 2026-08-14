<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartyApprovalRules extends Model
{
    use HasFactory;

    protected $table = 'party_approval_rules';

    protected $fillable = ['branch','accesslevel','approver','approval_type', 'approval_status','approval_required_users','approval_signatory_owner'];
}
