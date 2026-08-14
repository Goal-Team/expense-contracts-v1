<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FlowActivity extends Model
{
    use HasFactory;

    protected $table = 'flow_activity';

    protected $fillable = ['contract_id', 'current_data', 'updated_data', 'created_by'];
}

