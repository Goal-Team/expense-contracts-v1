<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPartiesRepresentative extends Model
{
    use HasFactory;

    protected $table = 'contract_parties_representative';

    protected $fillable = ['representative_name','representative_email','representative_designation','representative_contact','representative_nationality','representative_brs','passport_number','status','created_at','updated_at','created_by','updated_by'];

    protected $attributes = [
           'status' => 1
        ];
}
