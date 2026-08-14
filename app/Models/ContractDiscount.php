<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractDiscount extends Model
{
    use HasFactory;

    protected $table = 'contract_discounts';

    protected $fillable = [
        'contract_id',
        'category',
        'subcategory',
        'discount_percent',
        'room_charges',
    ];

    protected $casts = [
        'room_charges' => 'array',
        'discount_percent' => 'decimal:2',
    ];

    /**
     * Get the contract that owns the discount.
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }
}