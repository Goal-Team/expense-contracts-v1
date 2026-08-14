<?php

namespace App\Models;
use App\Models\Scopes\BranchScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BranchUser extends Model
{
    use HasFactory;

    protected $table = 'branch';

    protected $fillable = ['BranchName','branchstatus','Country'];
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new BranchScope());  
    }
}
