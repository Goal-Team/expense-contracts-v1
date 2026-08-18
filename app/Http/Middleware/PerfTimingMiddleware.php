<?php

namespace App\Http\Middleware;

use App\Perf\PerfRecorder;
use Closure;
use Illuminate\Http\Request;

/**
 * Outermost timing middleware. Prepended to the global stack by
 * App\Providers\PerfTimingServiceProvider, which only registers it when
 * APP_DEBUG is true and APP_ENV is 'local'.
 *
 * Writes nothing into the response body — the record goes to
 * storage/logs/perf-Y-m-d.log.
 */
class PerfTimingMiddleware
{
    public function __construct(protected PerfRecorder $perf)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        $this->perf->mark('middleware_in');

        return $next($request);
    }

    /**
     * Runs after the response has been sent to the client, so it is the latest
     * honest timestamp we can take inside the framework.
     */
    public function terminate(Request $request, $response): void
    {
        $this->perf->write(
            $request->getMethod(),
            '/'.ltrim($request->path(), '/'),
            method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null
        );
    }
}
