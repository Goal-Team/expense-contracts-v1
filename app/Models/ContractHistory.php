<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helpers;

class ContractHistory extends Model
{
    use HasFactory;

    protected $table = 'contracts_history';
  

    protected $fillable = [
        'contract_mode',
        'id',
        'contract_name',
        'contract_type',
        'contract_description',
        'signing_date',
        'commencement_type',
        'fixed_date',
        'event_name',
        'end_contract_type',
        'contract_end_date',
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
        'reminder_first_alert',
        'reminder_first_alertMeOn',
        'reminder_first_alert_repeats',
        'reminder_second_alert',
        'reminder_second_alertMeOn',
        'reminder_second_alert_repeats',
        'reminder_escalation_alert',
        'reminder_escalation_alertMeOn',
        'reminder_escalation_alert_repeats',
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
        'status',
        'parentcontract',
        'substatus',
        'reasonforterminate',
        'created_by',
        'updated_by'
    ];
    
    protected static function booted()
    {
        static::creating(function ($model) {
            if (is_null($model->created_by)) {
                $model->created_by = Helpers::userInfo()->id ?? 0;
            }
        });

        // static::retrieving(function ($model) {
        //     if ($model->created_by <= 0) {
        //         $model->created_by = Helpers::userInfo()->id ?? 0;
        //     }
        // });
    }     
 
}
