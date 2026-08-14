<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'contracts/setasign/*',
        'contracts/external/setasign/*',
        '/health-checks-api/get-list',
        'contracts/esign/docresponse'
    ];
}
