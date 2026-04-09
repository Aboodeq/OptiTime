<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduleSetting extends Model
{
    use HasUuids;

    protected $table = 'schedule_settings';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'day_start', 'day_end', 'slot_minutes', 'gap_minutes',
        'max_daily_lectures', 'capacity_threshold',
    ];

    public function constraints(): HasMany
    {
        return $this->hasMany(ScheduleSettingConstraint::class, 'schedule_setting_id');
    }

    public function roomConstraints(): HasMany
    {
        return $this->hasMany(ScheduleSettingRoomConstraint::class, 'schedule_setting_id');
    }

    public function studyDays(): HasMany
    {
        return $this->hasMany(ScheduleSettingStudyDay::class, 'schedule_setting_id');
    }

    public function breakTimes(): HasMany
    {
        return $this->hasMany(ScheduleSettingBreakTime::class, 'schedule_setting_id');
    }

    public function loadRanges(): HasMany
    {
        return $this->hasMany(ScheduleSettingLoadRange::class, 'schedule_setting_id');
    }
}
