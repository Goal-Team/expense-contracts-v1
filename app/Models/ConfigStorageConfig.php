<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfigStorageConfig extends Model
{
    use HasFactory;
    protected $table = 'contract_storage_config';
    public $timestamps = false;  
    protected $fillable = [ 'storage_type', 'config_key', 'active'];

}
