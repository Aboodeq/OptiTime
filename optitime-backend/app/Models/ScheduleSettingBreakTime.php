<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSettingBreakTime extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'schedule_setting_id',
        'break_key',
        'start_time',
        'end_time',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function scheduleSetting(): BelongsTo
    {
        return $this->belongsTo(ScheduleSetting::class, 'schedule_setting_id');
    }
}
