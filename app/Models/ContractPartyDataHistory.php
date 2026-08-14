<?php

namespace App\Models;

use App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractPartyDataHistory extends Model
{
    use HasFactory;
    protected $table = 'contract_party_data_history';
    protected $fillable = ['history_id','id','custom_field_group_id', 'contract_party_type', 'contract_party_exe_id', 'contract_party_id','contract_party_location_id', 'party_sub_type', 'vendor_code', 'party_address', 'contact_details'];
    public $timestamps = false;
    
    
    public static function whereInMultiple(array $columns, $values)
    {
        $values = array_map(function (array $value) {
            print_r($value);
            //exit;
            return "('".implode($value, "', '")."')"; 
        }, $values);

        return static::query()->whereRaw(
            '('.implode($columns, ', ').') in ('.implode($values, ', ').')'
        );
    }
}
