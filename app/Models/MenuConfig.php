<?php

namespace App\Models;

use App\Menu\MenuDataResolver;
use Illuminate\Database\Eloquent\Model;

class MenuConfig extends Model
{
    protected $table = 'menu_configs';

    protected $fillable = [
        'menu_type',
        'role',
        'menu_json',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Retire the cached menus whenever a row is written.
     *
     * A model hook rather than calls in MenuConfigController's four write methods: it covers every
     * writer, including tinker, a seeder, or a screen added later, and there is one place to read
     * instead of four to keep in step. Change G, ticket 23.
     */
    protected static function booted(): void
    {
        static::saved(fn () => MenuDataResolver::flush());
        static::deleted(fn () => MenuDataResolver::flush());
    }

    /**
     * Return decoded menu JSON as `menu` attribute.
     */
    public function getMenuAttribute()
    {
        return $this->menu_json ? json_decode($this->menu_json) : null;
    }
}
