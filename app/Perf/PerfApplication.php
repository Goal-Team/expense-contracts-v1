<?php

namespace App\Perf;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

/**
 * Local-only Application subclass that times each framework bootstrapper and
 * each service provider's register() and boot() individually.
 *
 * Selected by bootstrap/app.php only when storage/perf-boot-timing.enabled
 * exists; otherwise the stock Illuminate\Foundation\Application is used and this
 * class is never loaded. It adds behaviour to nothing — every override calls
 * parent and only wraps it in a microtime pair.
 *
 * Removal: delete this file, delete app/Perf/PerfBootTimer.php, and restore the
 * two-line `new Illuminate\Foundation\Application(...)` in bootstrap/app.php.
 */
class PerfApplication extends Application
{
    public function __construct($basePath = null)
    {
        PerfBootTimer::mark('app_construct_start');
        parent::__construct($basePath);
        PerfBootTimer::mark('app_construct_end');
    }

    /**
     * Time each of the six framework bootstrappers (LoadEnvironmentVariables,
     * LoadConfiguration, HandleExceptions, RegisterFacades, RegisterProviders,
     * BootProviders). Mirrors Application::bootstrapWith() exactly, including
     * the two dispatched events, so behaviour is unchanged.
     */
    public function bootstrapWith(array $bootstrappers)
    {
        PerfBootTimer::mark('bootstrap_with_start');

        $this->hasBeenBootstrapped = true;

        foreach ($bootstrappers as $bootstrapper) {
            $this['events']->dispatch('bootstrapping: '.$bootstrapper, [$this]);

            $t = microtime(true);
            $this->make($bootstrapper)->bootstrap($this);
            PerfBootTimer::add('bootstrapper', $bootstrapper, (microtime(true) - $t) * 1000);

            $this['events']->dispatch('bootstrapped: '.$bootstrapper, [$this]);
        }

        PerfBootTimer::mark('bootstrap_with_end');
    }

    public function register($provider, $force = false)
    {
        $label = is_string($provider) ? $provider : get_class($provider);

        PerfBootTimer::enter();
        $t = microtime(true);
        try {
            $result = parent::register($provider, $force);
        } finally {
            $ms = (microtime(true) - $t) * 1000;
            PerfBootTimer::leave();
            PerfBootTimer::add('register', $label, $ms);
        }

        return $result;
    }

    protected function bootProvider(ServiceProvider $provider)
    {
        $t = microtime(true);
        try {
            $result = parent::bootProvider($provider);
        } finally {
            PerfBootTimer::add('boot', get_class($provider), (microtime(true) - $t) * 1000);
        }

        return $result;
    }
}
