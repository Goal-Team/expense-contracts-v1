<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Helpers\Helpers;

class ApprovalContracts extends Model
{
    use HasFactory;

    protected $table = 'approval_contracts';

    protected $fillable = [
        'username',
        'unique_id',
        'status',
        'orderval',
        'previous_status',
        'contract_id',
        'next_action_item',
        'next_action_description',
        'button_text',
        'attachments',
        'attachments_filename',
        'approval_status',
        'flag',
        'next_status',
        'fileType',
        'signed_png',
        'signed_type',
        'updated_on',
        'created_by',
        'updated_by',
        'approval_type_main',
        'approval_type_row',
        'approver_type_row',
        'group_key',
        'stage_type',
        'stage_origin',
        'auto_next_enabled',
        'awaiting_owner_trigger',
        'dynamic_approver_enabled',
        'next_group_on_approve',
        'next_group_on_reject',
        'flow_type',
        'stage_name',
        'superseded',
        'file_permission'
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {

            if (is_null($model->created_by)) {
                $model->created_by = json_encode(['email' => Helpers::userInfo()->email ?? 'User', 'name' => Helpers::userInfo()->FirstName ?? 'Inactive']);
            }
            $model->fileType = fileStorageType();
        });
    }
    
    public static function prepareData(array $model): array
    {
            if (!isset($model['created_by'])) {
                $model['created_by'] = json_encode(['email' => Helpers::userInfo()->email ?? 'User', 'name' => Helpers::userInfo()->FirstName ?? 'Inactive']);
            }
            $model['fileType'] = fileStorageType();
    
        return $model;
    }    
}
