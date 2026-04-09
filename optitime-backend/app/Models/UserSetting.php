<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'email_schedule_updates',
        'email_reminders',
        'push_announcements',
        'push_system_alerts',
        'weekly_digest',
    ];

    protected $casts = [
        'email_schedule_updates' => 'boolean',
        'email_reminders' => 'boolean',
        'push_announcements' => 'boolean',
        'push_system_alerts' => 'boolean',
        'weekly_digest' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
