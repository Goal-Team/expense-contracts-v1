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

    // Helper: Get setting by key
    public static function getValue(string $key, $default = null)
    {
        return static::where('admin_key', $key)->value('admin_value') ?? $default;
    }

    // Helper: Set or update
    public static function setValue(string $key, $value, bool $active = true)
    {
        return static::updateOrCreate(
            ['admin_key' => $key],
            ['admin_value' => $value, 'active' => $active]
        );
    }
}

?>