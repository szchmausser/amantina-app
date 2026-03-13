<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting value by key, with an optional default.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        $setting = static::where('key', $key)->first();

        return $setting?->value ?? $default;
    }
}
