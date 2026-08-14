<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpActions extends Model
{
    use HasFactory;

    protected $table = 'otp_actions';

    protected $fillable = ['otp_number','otp_ref','otp_type'];
}
