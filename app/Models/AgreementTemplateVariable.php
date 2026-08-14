<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementTemplateVariable extends Model
{
    protected $table = 'agreement_template_variables';

    protected $fillable = [
        'agreement_template_id',
        'variable_key',
        'source',
        'required',
        'default_value',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    public function template()
    {
        return $this->belongsTo(AgreementTemplate::class, 'agreement_template_id');
    }
}
