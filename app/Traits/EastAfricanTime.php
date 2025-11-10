<?php

namespace App\Traits;

use Carbon\Carbon;

trait EastAfricanTime
{
    /**
     * Get the timestamp in East African Time
     */
    public function getEastAfricanTime($attribute)
    {
        if ($this->$attribute) {
            return Carbon::parse($this->$attribute)->setTimezone('Africa/Nairobi');
        }
        return null;
    }

    /**
     * Set timestamp ensuring it's stored correctly
     */
    public function setEastAfricanTime($attribute, $value)
    {
        if ($value) {
            // Parse the value and ensure it's in EAT, then convert to UTC for storage
            $eat_time = Carbon::parse($value, 'Africa/Nairobi');
            $this->$attribute = $eat_time->utc();
        }
    }

    /**
     * Get formatted East African date
     */
    public function getFormattedEATDate($attribute, $format = 'd M Y, H:i')
    {
        if ($this->$attribute) {
            return $this->getEastAfricanTime($attribute)->format($format);
        }
        return 'N/A';
    }

    /**
     * Boot the trait
     */
    protected static function bootEastAfricanTime()
    {
        static::creating(function ($model) {
            // Ensure timestamps are set correctly when creating
            if (!$model->created_at) {
                $model->created_at = Carbon::now('Africa/Nairobi')->utc();
            }
            if (!$model->updated_at) {
                $model->updated_at = Carbon::now('Africa/Nairobi')->utc();
            }
        });

        static::updating(function ($model) {
            // Ensure updated_at is set correctly when updating
            $model->updated_at = Carbon::now('Africa/Nairobi')->utc();
        });
    }
}