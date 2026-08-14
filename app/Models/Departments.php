<?php

namespace App\Models;
use App\Models\Scopes\DepartmentScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departments extends Model
{
    use HasFactory;

    protected $table = 'entitydepartment';
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new DepartmentScope());
    }    
}
