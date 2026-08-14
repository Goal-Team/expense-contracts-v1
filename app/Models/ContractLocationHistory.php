<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Helpers\Helpers;

class ContractLocationHistory extends Model
{
    use HasFactory;

    protected $table = 'contract_locations_history';
    
    protected $primaryKey = 'history_id';

    protected $fillable = [
        'contract_id',
        'snapshot_id',
        'location_id',
        'location_name',
        'action',
        'created_by',
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

    /**
     * Get the location details.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(LocationMaster::class, 'location_id', 'id');
    }
}
