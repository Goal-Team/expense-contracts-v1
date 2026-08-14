<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActionLog extends Model
{
    use HasFactory;
    protected $table = 'user_action_log';
    protected $primaryKey = 'log_id';
    public $timestamps = false;  
    protected $fillable = [ 'user_id', 'group_id', 'action_type', 'action_name', 'action_id', 'actioner_id', 'actioner_name', 'log_details', 'status', 'created_at', 'updated_at', 'active'];

}
