<?php

namespace App\Models;
use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClausesContractsLink extends Model
{
    use HasFactory;
    
    protected $table = 'clauses_contracts_link';   
    protected $fillable = ['clause_category', 'link_type', 'contract_id'];
    public $timestamps = false; 
}
