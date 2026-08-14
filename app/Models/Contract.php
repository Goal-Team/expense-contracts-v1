<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Scopes\ContractRoledBasedScope;

class Contract extends Model
{
    use HasFactory;

    protected $table = 'contracts';
  
    protected $with = ['contractPartyList'];

    protected $fillable = [
        'id',
        'contract_mode',
        'contract_name',
        'contract_type',
        'contract_tags',
        'contract_description',
        'signing_date',
        'commencement_type',
        'fixed_date',
        'contract_end_date',
        'event_name',
        'end_contract_type',
        'onetime_end_date',
        'fixedterm_end_date',
        'renewal_type',
        'period_auto_renewal',
        'period_auto_renewal_unit',
        'auto_renewal_date',
        'manual_renewal_date',
        'evergreen_condition',
        'termination_date',
        'termination_reason',
        'currency',
        'billing_value',
        'currency_value',
        'total_value',
        'payment_schedule',
        'currency_contract',
        'payment_terms',
        'billing_frequency',
        'taxes',
        'escalation_clauses',
        'discounts',
        'retention',
        'payment_escrow',
        'financial_guarantees',
        'currency_conversion',
        'reminder_enable',
        'reminder_first_alert',
        'reminder_first_alertMeOn',
        'reminder_first_alert_repeats',
        'reminder_second_alert',
        'reminder_second_alertMeOn',
        'reminder_second_alert_repeats',
        'reminder_escalation_alert',
        'reminder_escalation_alertMeOn',
        'reminder_escalation_alert_repeats',
        'reminder_escalation_alert_after',
        'reminder_escalation_alertMeOn_after',
        'reminder_escalation_alert_repeats_after',
        'contract_attachment',
        'contract_attachment_filename',
        'catgoery_id',
        'department_id',
        'rules_id',
        'signatory',
        'contract_status',
        'custom_fields_data',
        'confidentialityagreement',
        'exclusivity',
        'owner',
        'legal_advisor_id',
        'legal_advisor_email',
        'legal_contact_comment',
        'legal_requested_by_name',
        'legal_requested_by_email',
        'legal_requested_at',
        'legal_response_comment',
        'legal_responded_by_name',
        'legal_responded_by_email',
        'legal_responded_at',
        'legal_contact_status',
        'legal_finalized_notified_at',
        'status',
        'parentcontract',
        'substatus',
        'preapproval_stage',
        'preapproval_completed_at',
        'reasonforterminate',
        'contract_unique_id',
        'termination_remarks',
        'created_by',
        'fileMoved',
        'mm_code',
        'oracle_code',
        // Contract Create V3
        'tenure',
        'price_revision_type',
        'price_revision_value'
    ];
    
    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('accessLevelSelect', function ($builder) {
            $builder->select('*');
        });
        static::addGlobalScope(new ContractRoledBasedScope());
    }
    
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->storage_type = fileStorageType();
        });
    } 
    
    // Auto-decrypt contract_value
    public function getContractValueAttribute($value)
    {
        return decryptString($value, 'currency_value');
    }    
    
    /**
     * Get the party data list.
     */
    public function contractPartyList(): HasMany
    {
        return $this->hasMany(ContractPartyData::class, 'custom_field_group_id', 'id');
    }
 
     /**
     * Get the Contract that was parent
     */
    public function contractParent(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'id','parentcontract');
    }

     /**
     * Get the Contract Type
     */
    public function contractTypeData(): BelongsTo
    {
        return $this->belongsTo(ContractType::class, 'contract_type','contract_type_id');
    }

     /**
     * Get the Contract Clause Link
     */
    public function contractClauseLink(): HasMany
    {
        return $this->hasMany(ClausesContractsLink::class, 'contract_id', 'id');
    }
    
    
    /**
     * Get the contract discounts
     */
    public function contractDiscounts(): HasMany
    {
        return $this->hasMany(ContractDiscount::class, 'contract_id', 'id');
    }
    
    /**
     * Get the contract health checks
     */
    public function contractHealthChecks(): HasMany
    {
        return $this->hasMany(ContractHealthCheck::class, 'contract_id', 'id');
    }
    
    /**
     * Get the contract locations
     */
    public function contractLocations(): HasMany
    {
        return $this->hasMany(ContractLocation::class, 'contract_id', 'id');
    }    
}
