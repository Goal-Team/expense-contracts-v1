<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Companyprofile extends Model
{
    use HasFactory;
    protected $table = 'companyprofile';
    protected $fillable = ['id', 'entityname', 'buildingname', 'streetname', 'areaname', 'landmark', 'country', 'state', 'city', 'pincode', 'temp_buildingname', 'temp_streetname', 'temp_areaname', 'temp_landmark', 'temp_country', 'temp_state', 'temp_city', 'temp_pincode', 'fiscalyear', 'companytype', 'industrytype', 'servicedescription', 'branches', 'commercialbuilding', 'factory', 'retailstore', 'rentedoffice', 'warehouses', 'sap', 'bot', 'employee', 'question', 'entityid', 'answers', 'config_start_date', 'setting_edit', 'reportsettings'];
    public $timestamps = false;    
    
    
     /**
     * Get the Contract Party Country
     */
    public function countryDetails(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country','id');
    }     

     /**
     * Get the Contract Party State
     */
    public function stateDetails(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state','id');
    }     
    
}
