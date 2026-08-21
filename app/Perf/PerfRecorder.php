<?php

namespace App\Perf;

use Illuminate\Database\Events\QueryExecuted;

/**
 * Local-only request performance recorder.
 *
 * Collects timing marks, query aggregates and memory for a single request and
 * appends one JSON object per line to storage/logs/perf-Y-m-d.log.
 *
 * This whole directory is throwaway instrumentation. See
 * App\Providers\PerfTimingServiceProvider for the gate and the wiring.
 */
class PerfRecorder
{
    /** Named timing marks, microtime(true) floats. */
    protected array $marks = [];

    /** Total queries seen. */
    protected int $queryCount = 0;

    /** Total DB time in ms as reported by the driver. */
    protected float $queryMs = 0.0;

    /**
     * Aggregate per normalised SQL shape:
     *   md5(normalised) => ['sql' => sample, 'n' => count, 'ms' => total ms]
     * Bounded by MAX_GROUPS so a 12k-query request cannot blow memory.
     */
    protected array $groups = [];

    /** Slowest individual queries, kept bounded: [['sql'=>..,'ms'=>..], ..]. */
    protected array $slowest = [];

    protected bool $groupsTruncated = false;

    protected bool $written = false;

    /** Free-form measured facts recorded by ad-hoc probes (e.g. the blade walk). */
    protected array $notes = [];

    /** Distinct SQL shapes we are willing to remember. */
    protected const MAX_GROUPS = 3000;

    /** How many slow queries to report. */
    protected const SLOW_KEEP = 10;

    /** Max characters of SQL text retained per sample. */
    protected const SQL_MAX = 400;

    public function __construct()
    {
        // LARAVEL_START is defined in index.php before the framework boots, so
        // it is the earliest honest timestamp available to us.
        $this->mark('request_start', defined('LARAVEL_START') ? LARAVEL_START : microtime(true));
    }

    /** Record a named mark. First write wins, so nested views do not overwrite. */
    public function mark(string $name, ?float $at = null): void
    {
        if (! array_key_exists($name, $this->marks)) {
            $this->marks[$name] = $at ?? microtime(true);
        }
    }

    /** Record a named mark, overwriting any previous value (last one wins). */
    public function markLast(string $name, ?float $at = null): void
    {
        $this->marks[$name] = $at ?? microtime(true);
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->marks);
    }

    /**
     * Fold one executed query into the aggregates. Nothing per-query is kept
     * beyond the bounded group map and the bounded slow list, so memory stays
     * flat regardless of query count. (This is deliberately not
     * DB::enableQueryLog() / DB::getQueryLog(), which retains every query and
     * all of its bindings for the life of the request.)
     */
    public function recordQuery(QueryExecuted $event): void
    {
        $ms = (float) $event->time;
        $this->queryCount++;
        $this->queryMs += $ms;

        $sql = $this->truncate($this->squash($event->sql));
        $key = md5($this->normalise($event->sql));

        if (isset($this->groups[$key])) {
            $this->groups[$key]['n']++;
            $this->groups[$key]['ms'] += $ms;
        } elseif (count($this->groups) < self::MAX_GROUPS) {
            $this->groups[$key] = ['sql' => $sql, 'n' => 1, 'ms' => $ms];
        } else {
            $this->groupsTruncated = true;
        }

        // Bounded insertion into the slow list.
        if (count($this->slowest) < self::SLOW_KEEP) {
            $this->slowest[] = ['sql' => $sql, 'ms' => $ms];
            usort($this->slowest, fn ($a, $b) => $b['ms'] <=> $a['ms']);
        } elseif ($ms > $this->slowest[self::SLOW_KEEP - 1]['ms']) {
            $this->slowest[self::SLOW_KEEP - 1] = ['sql' => $sql, 'ms' => $ms];
            usort($this->slowest, fn ($a, $b) => $b['ms'] <=> $a['ms']);
        }
    }

    /**
     * Collapse a SQL string to a shape so that the same statement with
     * different bindings groups together. Placeholders are already `?` for
     * anything the query builder bound, but inline literals appear in raw
     * expressions, so those are folded too.
     */
    protected function normalise(string $sql): string
    {
        $s = $this->squash($sql);
        $s = preg_replace("/'(?:[^'\\\\]|\\\\.)*'/", '?', $s);        // string literals
        $s = preg_replace('/\b\d+(?:\.\d+)?\b/', '?', $s);            // numeric literals
        $s = preg_replace('/\(\s*\?(?:\s*,\s*\?)+\s*\)/', '(?)', $s); // in (?, ?, ?) -> in (?)
        return strtolower($s);
    }

    protected function squash(string $sql): string
    {
        return trim(preg_replace('/\s+/', ' ', $sql));
    }

    protected function truncate(string $s): string
    {
        return strlen($s) > self::SQL_MAX ? substr($s, 0, self::SQL_MAX).' …' : $s;
    }

    protected function ms(string $from, string $to): ?float
    {
        if (! isset($this->marks[$from], $this->marks[$to])) {
            return null;
        }
        return round(($this->marks[$to] - $this->marks[$from]) * 1000, 2);
    }

    /**
     * Build the log record. $method/$path/$status are passed in so the recorder
     * never needs to touch the request or response objects itself.
     */
    public function payload(string $method, string $path, ?int $status): array
    {
        $this->markLast('request_end');

        $dupGroups = array_filter($this->groups, fn ($g) => $g['n'] > 1);
        uasort($dupGroups, fn ($a, $b) => $b['n'] <=> $a['n']);

        $topDup = [];
        foreach (array_slice($dupGroups, 0, self::SLOW_KEEP) as $g) {
            $topDup[] = ['n' => $g['n'], 'total_ms' => round($g['ms'], 2), 'sql' => $g['sql']];
        }

        $slow = array_map(
            fn ($q) => ['ms' => round($q['ms'], 2), 'sql' => $q['sql']],
            $this->slowest
        );

        return [
            'ts'      => date('c'),
            'method'  => $method,
            'path'    => $path,
            'status'  => $status,
            'total_ms' => $this->ms('request_start', 'request_end'),
            'phases' => [
                // index.php entry -> our outermost global middleware.
                'bootstrap_ms'         => $this->ms('request_start', 'middleware_in'),
                // global middleware stack + route resolution.
                'routing_ms'           => $this->ms('middleware_in', 'route_matched'),
                // route/group middleware between RouteMatched and controller entry.
                'route_middleware_ms'  => $this->ms('route_matched', 'controller_entry'),
                // controller body, up to the moment the response is prepared.
                'controller_ms'        => $this->ms('controller_entry', 'preparing_response'),
                // blade compile + render (view->render() happens inside prepareResponse).
                'view_render_ms'       => $this->ms('preparing_response', 'response_prepared'),
                // response prepared -> after send (terminate).
                'send_terminate_ms'    => $this->ms('response_prepared', 'request_end'),
            ],
            'view' => [
                // Cross-check on view_render_ms: first View composer callback fired.
                'first_composing_ms' => $this->ms('request_start', 'first_composing'),
                'first_view'         => $this->marks['first_view_name'] ?? null,
                'views_composed'     => (int) ($this->marks['views_composed'] ?? 0),
            ],
            'db' => [
                'query_count'      => $this->queryCount,
                'total_ms'         => round($this->queryMs, 2),
                'distinct_shapes'  => count($this->groups),
                'duplicate_groups' => count($dupGroups),
                'duplicate_execs'  => array_sum(array_map(fn ($g) => $g['n'], $dupGroups)),
                'shapes_truncated' => $this->groupsTruncated,
                'top_duplicates'   => $topDup,
                'slowest'          => $slow,
            ],
            'memory' => [
                'peak_bytes' => memory_get_peak_usage(true),
                'peak_mb'    => round(memory_get_peak_usage(true) / 1048576, 2),
            ],
            // Per-bootstrapper / per-provider breakdown of the bootstrap phase.
            // Empty unless storage/perf-boot-timing.enabled exists (see
            // App\Perf\PerfApplication and bootstrap/app.php).
            'boot' => PerfBootTimer::report(),
            // Opcache status as seen by *this* SAPI — the CLI and FastCGI php.ini
            // may differ and only the web one matters.
            'opcache' => [
                'sapi' => PHP_SAPI,
                'ini_file' => php_ini_loaded_file() ?: null,
                'ext_loaded' => extension_loaded('Zend OPcache'),
                'enabled' => function_exists('opcache_get_status')
                    ? (bool) (@ini_get('opcache.enable'))
                    : false,
                'cached_scripts' => function_exists('opcache_get_status')
                    ? (@opcache_get_status(false)['opcache_statistics']['num_cached_scripts'] ?? null)
                    : null,
            ],
            'files' => [
                // With no opcache every one of these is re-parsed and re-compiled
                // on every request.
                'included' => count(get_included_files()),
            ],
            'notes' => $this->notes,
        ];
    }

    /** Increment a plain counter kept alongside the marks. */
    public function bump(string $name): void
    {
        $this->marks[$name] = ($this->marks[$name] ?? 0) + 1;
    }

    public function setNote(string $name, $value): void
    {
        $this->marks[$name] = $value;
    }

    /** Record an arbitrary measured fact into the `notes` block of the record. */
    public function note(string $name, $value): void
    {
        $this->notes[$name] = $value;
    }

    /** Add to a numeric note (counter / accumulated ms). */
    public function addTo(string $name, float $delta): void
    {
        $this->notes[$name] = round(($this->notes[$name] ?? 0) + $delta, 3);
    }

    /**
     * Static convenience so a blade template can probe without resolving the
     * container: PerfRecorder::probe('name', 1).
     */
    public static function probe(string $name, float $delta = 1): void
    {
        try {
            if (app()->bound(self::class)) {
                app(self::class)->addTo($name, $delta);
            }
        } catch (\Throwable $e) {
            // never break the thing being measured
        }
    }

    /** Append one JSON line. Never allowed to break the request. */
    public function write(string $method, string $path, ?int $status): void
    {
        if ($this->written) {
            return;
        }
        $this->written = true;

        try {
            $dir = storage_path('logs');
            $file = $dir.DIRECTORY_SEPARATOR.'perf-'.date('Y-m-d').'.log';
            $line = json_encode(
                $this->payload($method, $path, $status),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
            );
            if ($line !== false) {
                file_put_contents($file, $line.PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        } catch (\Throwable $e) {
            // Instrumentation must never affect the thing it measures.
        }
    }
}
