<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\Helpers;

class ContractHealthCheckHistory extends Model
{
    use HasFactory;

    protected $table = 'contract_health_checks_history';
    
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'contract_id',
        'snapshot_id',
        'original_id',
        'row_name',
        'selected_test_ids',
        'package_price',
        'selected_consultation_ids',
        'consultation_prices',
        'overhead_allocation',
        'approved_cost',
        'action',
        'created_by',
    ];

    protected $casts = [
        'selected_test_ids' => 'array',
        'selected_consultation_ids' => 'array',
        'consultation_prices' => 'array',
        'package_price' => 'decimal:2',
        'overhead_allocation' => 'decimal:2',
        'approved_cost' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (is_null($model->created_by)) {
                $model->created_by = Helpers::userInfo()->id ?? 0;
            }
        });
    }

    /**
     * Get the contract history snapshot this belongs to.
     */
    public function contractHistory(): BelongsTo
    {
        return $this->belongsTo(ContractHistory::class, 'snapshot_id', 'history_id');
    }
}
