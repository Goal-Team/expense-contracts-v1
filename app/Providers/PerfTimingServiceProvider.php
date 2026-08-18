<?php

namespace App\Providers;

use App\Http\Middleware\PerfTimingMiddleware;
use App\Perf\PerfRecorder;
use Closure;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Routing\Events\ResponsePrepared;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Local-only request timing instrumentation.
 *
 * Everything here is inert unless APP_DEBUG is true AND APP_ENV is exactly
 * 'local'. Nothing is written into the response body; one JSON object per
 * request is appended to storage/logs/perf-Y-m-d.log.
 *
 * To remove the instrumentation entirely: delete this file,
 * app/Http/Middleware/PerfTimingMiddleware.php and app/Perf/PerfRecorder.php,
 * and drop the single registration line from config/app.php.
 */
class PerfTimingServiceProvider extends ServiceProvider
{
    /** Container key for the innermost "controller entry" marker middleware. */
    public const ENTRY_MARKER = 'perf.controller-entry';

    protected function enabled(): bool
    {
        // trim(): the local .env has a trailing space after APP_ENV=local.
        return filter_var(config('app.debug'), FILTER_VALIDATE_BOOLEAN)
            && trim((string) config('app.env')) === 'local';
    }

    public function register(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->app->singleton(PerfRecorder::class);

        // Innermost pipe: attached to the matched route so it runs after every
        // group/route middleware and immediately before the controller action.
        $this->app->bind(self::ENTRY_MARKER, function ($app) {
            $perf = $app->make(PerfRecorder::class);

            return new class($perf)
            {
                public function __construct(private PerfRecorder $perf)
                {
                }

                public function handle(Request $request, Closure $next)
                {
                    $this->perf->mark('controller_entry');

                    return $next($request);
                }
            };
        });
    }

    public function boot(): void
    {
        if (! $this->enabled() || $this->app->runningInConsole()) {
            return;
        }

        $perf = $this->app->make(PerfRecorder::class);

        // Outermost pipe: first thing in the global middleware stack.
        $kernel = $this->app->make(HttpKernelContract::class);
        if ($kernel instanceof HttpKernel) {
            $kernel->prependMiddleware(PerfTimingMiddleware::class);
        }

        // Query aggregates. DB::listen is used rather than
        // DB::enableQueryLog()/getQueryLog() because the query log retains every
        // statement and its bindings for the whole request; at ~12k queries that
        // is tens of MB of avoidable allocation inside the thing we are
        // measuring. The recorder folds each event into bounded aggregates.
        DB::listen(fn (QueryExecuted $q) => $perf->recordQuery($q));

        Event::listen(RouteMatched::class, function (RouteMatched $e) use ($perf) {
            $perf->mark('route_matched');
            $e->route->middleware(self::ENTRY_MARKER);
        });

        Event::listen(PreparingResponse::class, fn () => $perf->mark('preparing_response'));
        Event::listen(ResponsePrepared::class, fn () => $perf->markLast('response_prepared'));

        // Cross-check on the render boundary: the first view composed.
        Event::listen('composing: *', function ($eventName, $payload) use ($perf) {
            if (! $perf->has('first_composing')) {
                $perf->mark('first_composing');
                $perf->setNote('first_view_name', str_replace('composing: ', '', $eventName));
            }
            $perf->bump('views_composed');
        });

        // Fallback: if terminate() never runs (uncaught error, dd(), exit) still
        // emit the record.
        register_shutdown_function(function () use ($perf) {
            $request = $this->app->bound('request') ? $this->app->make('request') : null;
            $perf->write(
                $request ? $request->getMethod() : 'CLI',
                $request ? '/'.ltrim($request->path(), '/') : '-',
                http_response_code() ?: null
            );
        });
    }
}
