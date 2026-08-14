<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractAnnexure extends Model
{
    use HasFactory;

    protected $table = 'contract_annexure';

    protected $fillable = [
        'contract_id',
        'annexure_master_id',
        'annexure_name',
        'title',
        'file_path',
        'file_name',
        'sort_order',
        'created_by',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function annexureMaster(): BelongsTo
    {
        return $this->belongsTo(AnnexureMaster::class, 'annexure_master_id');
    }
}
