<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class LoanPolicyControl extends Model
{
    protected $table = 'loan_policy_controls';

    protected $fillable = [
        'key',
        'label',
        'description',
        'value',
        'format',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    /**
     * Retrieve the numeric value for a policy key.
     * Reads from cache (1 hour), falls back to $default if key is missing.
     */
    public static function getValue(string $key, float $default = 0): float
    {
        $controls = Cache::remember('loan_policy_controls', 3600, function () {
            return static::all()->pluck('value', 'key')->all();
        });

        return isset($controls[$key]) ? (float) $controls[$key] : $default;
    }

    /**
     * Clear the cached policy controls (call after any update).
     */
    public static function clearCache(): void
    {
        Cache::forget('loan_policy_controls');
    }

    /**
     * Format a value for display based on its format type.
     */
    public function getDisplayValueAttribute(): string
    {
        return match ($this->format) {
            'percent'    => round($this->value * 100, 2) . '%',
            'multiplier' => number_format($this->value, 2) . 'x',
            'score'      => (int) $this->value . '',
            'integer'    => (int) $this->value . '',
            default      => (string) $this->value,
        };
    }
}
