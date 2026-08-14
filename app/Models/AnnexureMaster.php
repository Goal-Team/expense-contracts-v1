<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnexureMaster extends Model
{
    use HasFactory;

    protected $table = 'annexure_master';

    protected $fillable = [
        'annexure_name',
        'title',
        'contract_type',
        'status',
        'sample_file',
        'sample_file_name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function contractType()
    {
        return $this->belongsTo(ContractType::class, 'contract_type', 'contract_type_id');
    }
}
