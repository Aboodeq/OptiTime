<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    const UPDATED_AT = null;

    protected $fillable = [
        'actor_user_id', 'event_type', 'module', 'action', 'description',
        'target_table', 'target_id', 'request_id', 'ip_address', 'user_agent',
        'metadata', 'before_data', 'after_data',
    ];

    protected $casts = [
        'metadata' => 'array',
        'before_data' => 'array',
        'after_data' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
