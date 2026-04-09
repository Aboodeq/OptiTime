<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleSettingConstraint extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'schedule_setting_id', 'category', 'constraint_key', 'enabled', 'weight',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'weight' => 'float',
    ];

    public function scheduleSetting(): BelongsTo
    {
        return $this->belongsTo(ScheduleSetting::class, 'schedule_setting_id');
    }
}
