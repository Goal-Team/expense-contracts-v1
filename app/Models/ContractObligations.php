<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractObligations extends Model
{
    use HasFactory;
    protected $table = 'contract_obligations';

    protected $fillable = [
        'contract_id',
        'owner',
        'reviewer',
        'obligation_name',
        'description',
        'task_type',
        'priority',
        'due_date',
        'onetime_end_date',
        'recuring_due_date',
        'end_frequency',
        'repeats',
        'status',
        'frequency',
        'flag',
    ];

}
