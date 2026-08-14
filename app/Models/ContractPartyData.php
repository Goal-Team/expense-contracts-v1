<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractPartyData extends Model
{
    use HasFactory;
    protected $table = 'contract_party_data';
    protected $fillable = ['custom_field_group_id', 'contract_party_type', 'contract_party_exe_id', 'contract_party_id','contract_party_location_id', 'party_sub_type', 'vendor_code', 'party_address', 'contact_details'];
    public $timestamps = false;
    
    
    public static function whereInMultiple(array $columns, $values)
    {
        $values = array_map(function (array $value) {
            print_r($value);
            //exit;
            return "('".implode($value, "', '")."')"; 
        }, $values);

        return static::query()->whereRaw(
            '('.implode($columns, ', ').') in ('.implode($values, ', ').')'
        );
    }
    
    /**
     * Get the party Details that was Internal.
     */
    public function partyDetailsIn(): BelongsTo
    {
        return $this->belongsTo(EntityMain::class, 'contract_party_id', 'id');
    }
    
    /**
     * Get the party Details that was External.
     */
    public function partyDetailsEx(): BelongsTo
    {
        return $this->belongsTo(ContractParties::class, 'contract_party_exe_id', 'id');
    }

     /**
     * Get the Contract Location
     */
    public function branchDetails(): BelongsTo
    {
        return $this->belongsTo(BranchUser::class, 'contract_party_location_id','id');
    } 
    
    
    public function contract()
    {
        return $this->belongsTo(Contract::class, 'custom_field_group_id');
    }
    
}
