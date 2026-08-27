<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSettings extends Model
{
    protected $fillable = ['admin_key', 'admin_value', 'active'];
    
    protected $table = 'admin_settings';

    protected $casts = [
        'admin_value' => 'array',
        'active' => 'boolean',
    ];

    /**
     * The values already read in this request, keyed by admin_key.
     *
     * A settings row cannot change while one request runs, unless that request writes it, and
     * setValue() clears the entry when it does. The contract detail page asked for
     * enable_role_based_data 64 times in one load - ContractRoledBasedScope reads it once per
     * Contract query - so the same row came back over the wire 64 times.
     *
     * A null value is a real answer here, so the test is array_key_exists and not isset.
     */
    protected static array $valueCache = [];

    // Helper: Get setting by key
    public static function getValue(string $key, $default = null)
    {
        if (! array_key_exists($key, static::$valueCache)) {
            static::$valueCache[$key] = static::where('admin_key', $key)->value('admin_value');
        }

        // The default is applied on every read, not stored, so two callers may pass different ones.
        return static::$valueCache[$key] ?? $default;
    }

    // Helper: Set or update
    public static function setValue(string $key, $value, bool $active = true)
    {
        unset(static::$valueCache[$key]);

        return static::updateOrCreate(
            ['admin_key' => $key],
            ['admin_value' => $value, 'active' => $active]
        );
    }

    /**
     * Drop the request cache. For tests, and for any code that writes admin_settings without
     * going through setValue().
     */
    public static function forgetCachedValues(): void
    {
        static::$valueCache = [];
    }

    /**
     * AdminConfigSettingsController writes rows straight through create() and update(), not
     * through setValue(), so the cache is cleared on the model's own save and delete events as
     * well. Both of those requests redirect, so nothing reads the value again in the same
     * request today - this is here so that stays true if a caller changes.
     */
    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCachedValues());
        static::deleted(fn () => static::forgetCachedValues());
    }
}

?>