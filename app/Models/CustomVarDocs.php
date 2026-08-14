<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomVarDocs extends Model
{
    use HasFactory;
    protected $table = 'custom_var_docs';
    protected $primaryKey = 'var_id';
    public $timestamps = false;  
    protected $fillable = ['var_field', 'var_table', 'var_var', 'status'];
 
}
