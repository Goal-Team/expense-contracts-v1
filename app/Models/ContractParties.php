<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Scopes\PartiesRoleBasedScope;

class ContractParties extends Model
{
    use HasFactory;

    protected $table = 'contract_parties';

    protected $fillable = ['company_name', 'party_type', 'entity_scope', 'entity_type', 'payment_type', 'building_no', 'area_name', 'landmark', 'city', 'state', 'pincode', 'country', 'company_contact', 'company_email', 'website', 'representative_name', 'representative_email', 'representative_designation', 'representative_contact', 'legal_entity', 'organization_type', 'role_in_contract', 'pan', 'gst', 'gst_file', 'pan_file', 'corporate_registration_number', 'tax_residency_certificate', 'no_permanent_establishment', 'escalation_matrix', 'engagement_level', 'engagement_branch', 'engagement_access_level', 'custom_fields', 'is_related_party', 'status', 'created_at', 'updated_at', 'created_by', 'updated_by', 'approval_status','approvers', 'vendor_code', 'active_vendor_code', 'valid'];

    protected $casts = [
        'escalation_matrix' => 'array'
    ];

    protected $attributes = [];
    
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(new PartiesRoleBasedScope());
    }    


     /**
     * Get the Contract Party Country
     */
    public function countryDetails(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country','id');
    }     

     /**
     * Get the Contract Party State
     */
    public function stateDetails(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state','id');
    }   
    
     /**
     * Get the Contract Party Rep Details
     */
    public function repDetails(): HasMany
    {
        return $this->hasMany(ContractPartiesRepresentative::class, 'parties_id','id');
    }

    /**
     * Get the contract party data records linked to this party
     */
    public function contractPartyData(): HasMany
    {
        return $this->hasMany(\App\Models\ContractPartyData::class, 'contract_party_exe_id', 'id');
    }
}
