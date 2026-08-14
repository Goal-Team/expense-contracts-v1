<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsignResposnse extends Model
{
    use HasFactory;

    protected $table = 'esign_resposnse';
    
    protected $fillable = ['contract_id', 'approval_id', 'esignresponse','status'];
    
    protected $timestamp=false;
}
