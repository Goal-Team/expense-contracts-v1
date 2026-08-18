<?php

namespace App\Perf;

/**
 * Local-only bootstrap-phase timer.
 *
 * Static because it has to collect data from before the container exists (the
 * Application constructor and the framework bootstrappers both run long before
 * any service provider, and therefore long before PerfRecorder can be resolved).
 * PerfRecorder::payload() reads report() back out at the end of the request.
 *
 * Enabled only when the marker file storage/perf-boot-timing.enabled exists AND
 * bootstrap/app.php's gate picks App\Perf\PerfApplication. Delete the marker file
 * to turn it off with no code change; see PerfTimingServiceProvider's header for
 * full removal.
 */
class PerfBootTimer
{
    /** ['phase' => 'register'|'boot'|'bootstrapper', 'label' => class, 'ms' => float, 'depth' => int] */
    protected static array $events = [];

    /** Nesting depth of register() calls, so nested (inclusive) times are visible. */
    protected static int $depth = 0;

    /** microtime marks for the pre-provider stages. */
    protected static array $marks = [];

    public static function enabled(): bool
    {
        return is_file(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'storage'
            .DIRECTORY_SEPARATOR.'perf-boot-timing.enabled');
    }

    public static function mark(string $name): void
    {
        self::$marks[$name] = microtime(true);
    }

    public static function enter(): void
    {
        self::$depth++;
    }

    public static function leave(): void
    {
        self::$depth--;
    }

    public static function add(string $phase, string $label, float $ms): void
    {
        self::$events[] = [
            'phase' => $phase,
            'label' => $label,
            'ms' => round($ms, 2),
            'depth' => self::$depth,
        ];
    }

    /**
     * Grouped report. Nested register() calls are reported at depth > 0; their
     * time is also included in the parent's figure, so only depth-0 rows sum to
     * the total.
     */
    public static function report(): array
    {
        if (! self::$events && ! self::$marks) {
            return [];
        }

        $out = ['stages' => [], 'bootstrappers' => [], 'register' => [], 'boot' => [], 'nested_register' => []];

        $start = defined('LARAVEL_START') ? LARAVEL_START : null;
        if ($start !== null) {
            foreach (self::$marks as $name => $at) {
                $out['stages'][$name.'_at_ms'] = round(($at - $start) * 1000, 2);
            }
        }

        foreach (self::$events as $e) {
            $short = self::shorten($e['label']);
            if ($e['phase'] === 'bootstrapper') {
                $out['bootstrappers'][$short] = $e['ms'];
            } elseif ($e['phase'] === 'register') {
                if ($e['depth'] > 0) {
                    $out['nested_register'][$short] = ($out['nested_register'][$short] ?? 0) + $e['ms'];
                } else {
                    $out['register'][$short] = ($out['register'][$short] ?? 0) + $e['ms'];
                }
            } elseif ($e['phase'] === 'boot') {
                $out['boot'][$short] = ($out['boot'][$short] ?? 0) + $e['ms'];
            }
        }

        arsort($out['register']);
        arsort($out['boot']);
        arsort($out['nested_register']);

        $out['totals'] = [
            'bootstrappers_ms' => round(array_sum($out['bootstrappers']), 2),
            'register_top_level_ms' => round(array_sum($out['register']), 2),
            'boot_ms' => round(array_sum($out['boot']), 2),
            'providers_registered' => count($out['register']) + count($out['nested_register']),
            'providers_booted' => count($out['boot']),
        ];

        return $out;
    }

    protected static function shorten(string $class): string
    {
        // Keep vendor/namespace context but drop the noise.
        return str_replace(
            ['Illuminate\\Foundation\\Bootstrap\\', 'Illuminate\\', 'Modules\\', 'App\\Providers\\'],
            ['', 'Ill:', 'Mod:', 'App:'],
            $class
        );
    }
}
