<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalParties extends Model
{
    use HasFactory;

    protected $table = 'approval_parties';

    protected $fillable = [
        'username',
        'unique_id',
        'status',
        'orderval',
        'previous_status',
        'parties_id',
        'next_action_item',
        'next_action_description',
        'button_text',
        'attachments',
        'attachments_filename',
        'approval_status',
        'flag',
        'next_status',
        'fileType'
    ];
}
