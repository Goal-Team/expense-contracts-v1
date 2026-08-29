<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;

    protected $table = 'state';

    protected $fillable = ['Countryid','stateCode','name'];

    /**
     * Every state name in this request, keyed by id. Null until the first lookup.
     *
     * get_state() used to run one `select * from state where id = ?` per call, and every blade
     * that calls it calls it inside a loop over the party list. The create page asked 15,000
     * times in one load - 15,000 of its 15,094 queries, and about 98% of its database time.
     *
     * The table holds 32 rows, so the whole thing is loaded once and answered from memory after
     * that. A state row cannot change while one request runs.
     *
     * Same shape as AdminSettings::$valueCache, which the contract detail page effort added for
     * the same reason.
     */
    protected static ?array $nameCache = null;

    /**
     * The state name for an id, or 0 when the id is missing or unknown.
     *
     * 0 rather than null or '' because that is what get_state() has always returned and eight
     * blades print the result straight into the page.
     */
    public static function nameFor($id)
    {
        if (! ($id > 0)) {
            return 0;
        }

        if (static::$nameCache === null) {
            static::$nameCache = static::query()->pluck('name', 'id')->all();
        }

        return static::$nameCache[(int) $id] ?? 0;
    }

    /**
     * Drop the request cache. For tests, and for any code that writes the state table.
     */
    public static function forgetCachedNames(): void
    {
        static::$nameCache = null;
    }
}
