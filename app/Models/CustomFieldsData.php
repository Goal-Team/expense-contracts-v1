<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFieldsData extends Model
{
    use HasFactory;
    protected $table = 'custom_field_data';
    protected $fillable = ['custom_field_id', 'custom_field_group', 'custom_field_value' ,'custom_field_group_id'];
    public $timestamps = false;
}
