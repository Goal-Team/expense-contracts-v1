<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\HealthCheckMaster;
use App\Models\TestMaster;

class ContractHealthCheck extends Model
{
    use HasFactory;

    protected $table = 'contract_health_checks';

    protected $fillable = [
        'contract_id',
        'row_name',
        'selected_test_ids',
        'package_price',
        'selected_consultation_ids',
        'consultation_prices',
        'overhead_allocation',
        'approved_cost'
    ];

    protected $casts = [
        'selected_test_ids' => 'array',
        'selected_consultation_ids' => 'array',
        'consultation_prices' => 'array',
        'package_price' => 'decimal:2',
        'overhead_allocation' => 'decimal:2',
        'approved_cost' => 'decimal:2'
    ];

    /**
     * Get the contract that owns the health check.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }
    
    public function tests()
    {
        return TestMaster::whereIn('id', $this->selected_test_ids ?? []);
    }    
}