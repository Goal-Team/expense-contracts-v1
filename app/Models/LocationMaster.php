<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationMaster extends Model
{
    use HasFactory;

    protected $table = 'locations_master';

    protected $fillable = [
        'location_name',
        'location_code',
        'region',
        'address',
        'city',
        'state',
        'country',
        'pincode',
        'contact_person',
        'contact_email',
        'contact_phone',
        'status',
        // Regional Approvers
        'regional_verifier_name',
        'regional_verifier_email',
        'regional_approver_name',
        'regional_approver_email',
        'regional_signatory_name',
        'regional_signatory_email',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}