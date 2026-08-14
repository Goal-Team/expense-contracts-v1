<?php

namespace App\Models;
use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldsTimeline extends Model
{
    use HasFactory;
    protected $table = 'custom_field_timeline';

    public $timestamps = false;


}
