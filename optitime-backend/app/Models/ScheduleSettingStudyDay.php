<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSettingStudyDay extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'schedule_setting_id',
        'day_value',
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
