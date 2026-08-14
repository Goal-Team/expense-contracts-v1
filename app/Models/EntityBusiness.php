<?php

namespace App\Models;
use App\Models\Scopes\DepartmentScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntityBusiness extends Model
{
    use HasFactory;

    protected $table = 'entitybusiness';

    protected $fillable = ['name','entityid','bussinessid'];
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new DepartmentScope());
    }   
    
    // One department has many contracts
    public function contracts()
    {
        return $this->hasMany(Contract::class, 'department_id');
    }    
}
