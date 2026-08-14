<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tasks extends Model
{
    use HasFactory;
    protected $table = 'contract_tasks';

    protected $fillable = ['name_of_task','branch','status','start_date','end_date','priority','task_owner','task_reviewer','contract_id',
        'attachments', 'description'];

}
