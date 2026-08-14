<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementTemplateRender extends Model
{
    protected $table = 'agreement_template_renders';

    protected $fillable = [
        'agreement_template_id',
        'merge_input_json',
        'rendered_docx_path',
        'rendered_pdf_path',
        'render_status',
        'generated_by',
    ];

    protected $casts = [
        'merge_input_json' => 'array',
    ];

    public function template()
    {
        return $this->belongsTo(AgreementTemplate::class, 'agreement_template_id');
    }
}
