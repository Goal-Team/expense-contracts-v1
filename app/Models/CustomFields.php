<?php

namespace App\Models;
use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFields extends Model
{
    use HasFactory;
    protected $table = 'custom_field';   
    // protected $fillable = ['basic_details', 'contract_duration', 'financial_details'];  

    public $timestamps = false; 
      
 
}
