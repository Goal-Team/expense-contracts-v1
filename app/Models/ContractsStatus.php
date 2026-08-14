<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractsStatus extends Model
{
    use HasFactory;
    protected $table = 'contracts_status';    
    public $timestamps = false;   
    
    protected $fillable = [
        'status',
        'approval_status',
        'approval_data'
    ];
 
}
