<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiResponse extends Model
{
    use HasFactory;

    protected $table = 'ai_resposnse'; // Explicit table name if not pluralized automatically

    protected $fillable = [
        'contract_id', 'contract_temp_id', 'airesponse', 'status'
    ];
}
