<?php

namespace App\Traits;

use Illuminate\Support\Facades\Schema;

trait HandlesLegacyTimestamps
{
    /**
     * Get the appropriate order by clause for legacy data compatibility
     * 
     * @param string $table The table name to check
     * @param string $legacyColumn The legacy timestamp column name (default: datecreated)
     * @param string $standardColumn The standard Laravel timestamp column (default: created_at)
     * @return string
     */
    public function getLegacyTimestampOrder($table = null, $legacyColumn = 'datecreated', $standardColumn = 'created_at')
    {
        $table = $table ?: $this->getTable();
        
        // Check if both columns exist
        $hasLegacy = Schema::hasColumn($table, $legacyColumn);
        $hasStandard = Schema::hasColumn($table, $standardColumn);
        
        if ($hasLegacy && $hasStandard) {
            // Both exist, use COALESCE to prefer legacy for old data, standard for new
            return "COALESCE({$legacyColumn}, {$standardColumn}) DESC";
        } elseif ($hasLegacy) {
            // Only legacy exists (old tables)
            return "{$legacyColumn} DESC";
        } elseif ($hasStandard) {
            // Only standard exists (new tables)
            return "{$standardColumn} DESC";
        }
        
        // Fallback to id DESC if neither timestamp column exists
        return "id DESC";
    }

    /**
     * Scope a query to order by legacy timestamp
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $legacyColumn
     * @param string $standardColumn
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByLegacyTimestamp($query, $legacyColumn = 'datecreated', $standardColumn = 'created_at')
    {
        $orderClause = $this->getLegacyTimestampOrder($this->getTable(), $legacyColumn, $standardColumn);
        
        return $query->orderByRaw($orderClause);
    }
}