<?php

namespace App\Models;
use App\Models\Scopes\UserContractScope;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTempUser extends Model
{
    use HasFactory;

    protected $table = 'external_temp_user';

    protected $fillable = ['contract_id','accessSlug', '2FA', 'accessExpiryDate', 'password', 'is_active', 'email', 'name', 'opened', 'opened_date', 'ip_details', 'access_type'];

}
