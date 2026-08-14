<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\Helpers;

class ContractDiscountHistory extends Model
{
    use HasFactory;

    protected $table = 'contract_discounts_history';
    
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'contract_id',
        'snapshot_id',
        'original_id',
        'category',
        'subcategory',
        'discount_percent',
        'room_charges',
        'action',
        'created_by',
    ];

    protected $casts = [
        'room_charges' => 'array',
        'discount_percent' => 'decimal:2',
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
