<?php

namespace App\Models;
use App\Models\Scopes\ContractTypeScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
    use HasFactory;
    protected $table = 'contract_type'; 
    protected $primaryKey = 'contract_type_id';
    public $timestamps = false; 
      
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ContractTypeScope());  
    }    
 
}