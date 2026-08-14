<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegalAdvisor extends Model
{
    use HasFactory;

    protected $table = 'legal_advisors';

    protected $fillable = [
        'name',
        'email_id',
        'legal_name',
        'designation',
        'contact',
        'status',
    ];
}
