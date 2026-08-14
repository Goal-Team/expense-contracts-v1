<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPartiesLabel extends Model
{
    use HasFactory;

    protected $table = 'contract_parties_label';

    protected $fillable = ['name','label_name','status','created_at','updated_at','created_by','updated_by'];

    protected $attributes = [
           'status' => 1
        ];
}
