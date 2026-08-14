<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthCheckMaster extends Model
{
    use HasFactory;

    protected $table = 'health_check_masters';

    protected $fillable = [
        'test_name',
        'test_code',
        'description',
        'category',
        'default_price',
        'status',
    ];

    protected $casts = [
        'default_price' => 'decimal:2',
        'status' => 'boolean',
    ];
}