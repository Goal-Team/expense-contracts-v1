<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractTemplates extends Model
{
    use HasFactory;
    protected $table = 'contract_templates';
    protected $fillable = [
    'template_content',
    'contract_type',
    'payment_type',
    'entity_type_id',
    'status'
    ];    
    public $timestamps = false;   
 
}