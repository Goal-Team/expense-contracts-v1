<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractLocation extends Model
{
    use HasFactory;

    protected $table = 'contract_locations';

    protected $fillable = [
        'contract_id',
        'location_id',
    ];

    /**
     * Get the contract that owns the location. 
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id', 'id');
    }

    /**
     * Get the location details.
     */
    public function locationOld(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'location_id', 'id')
            ->select([
                'id',
                decrypt_data('BranchName', 'branch'),
                decrypt_data('branchstatus', 'branch'),
                decrypt_data('Doorno', 'branch'),
                decrypt_data('StreetName', 'branch'),
                decrypt_data('AreaName', 'branch'),
                decrypt_data('Landmark', 'branch'),
                decrypt_data('PinCode', 'branch'),
                'City',
                decrypt_data('ContactNumber', 'branch'),
                decrypt_data('branchheadname', 'branch'),
                decrypt_data('departments', 'branch'),
                decrypt_data('LegalName', 'branch'),
            ]);
    }
    
    /**
     * Get the location details.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(LocationMaster::class, 'location_id', 'id');
    }
}