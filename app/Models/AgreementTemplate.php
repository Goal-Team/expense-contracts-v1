<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementTemplate extends Model
{
    protected $table = 'agreement_templates';

    protected $fillable = [
        'contract_type',
        'payment_type',
        'entity_type_id',
        'template_name',
        'template_html',
        'source_docx_path',
        'source_docx_filename',
        'status',
        'version_no',
        'published_scope_key',
        'created_by',
        'updated_by',
    ];

    public function variables()
    {
        return $this->hasMany(AgreementTemplateVariable::class, 'agreement_template_id');
    }

    public function renders()
    {
        return $this->hasMany(AgreementTemplateRender::class, 'agreement_template_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeForScope($query, $contractType, $paymentType, $entityTypeId)
    {
        return $query
            ->where('contract_type', $contractType)
            ->where('payment_type', $paymentType)
            ->where('entity_type_id', $entityTypeId);
    }
}
