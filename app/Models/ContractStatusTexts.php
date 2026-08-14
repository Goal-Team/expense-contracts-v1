<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractStatusTexts extends Model
{
    use HasFactory;

    protected $table = 'contract_status_text';

    protected $fillable = ['main_status','sub_status','active'];
}
