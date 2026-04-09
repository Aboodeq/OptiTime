<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSettingLoadRange extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'schedule_setting_id',
        'load_key',
        'min_value',
        'max_value',
    ];

    public function scheduleSetting(): BelongsTo
    {
        return $this->belongsTo(ScheduleSetting::class, 'schedule_setting_id');
    }
}
