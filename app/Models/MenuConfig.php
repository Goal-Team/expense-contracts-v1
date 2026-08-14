<?php

namespace App\Models;

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
     * Return decoded menu JSON as `menu` attribute.
     */
    public function getMenuAttribute()
    {
        return $this->menu_json ? json_decode($this->menu_json) : null;
    }
}
