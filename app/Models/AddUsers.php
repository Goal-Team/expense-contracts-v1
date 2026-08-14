<?php

namespace App\Models;
use App\Models\Scopes\UserContractScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddUsers extends Model
{
    use HasFactory;

    protected $table = 'ContractUsers';

    protected $fillable = ['FirstName','LastName','Designation'];
    
        protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new UserContractScope());  
    }
}
