<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderSettings extends Model
{
    use HasFactory;

    protected $table = 'reminder_settings';

    protected $fillable = [
        'id',
        'reminder_severity',
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
        'created_by'
    ];
}
