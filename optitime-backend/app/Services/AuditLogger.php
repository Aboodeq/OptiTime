<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

final class AuditLogger
{
    public static function log(
        ?User $user,
        string $action,
        ?string $entityType = null,
        mixed $entityId = null,
        ?array $payload = null,
        ?Request $request = null,
    ): void {
        $targetId = null;
        if ($entityId !== null && $entityId !== '') {
            $targetId = (string) $entityId;
        }

        AuditLog::query()->create([
            'actor_user_id' => $user?->id,
            'event_type' => 'info',
            'module' => 'api',
            'action' => $action,
            'description' => $entityType,
            'target_table' => $entityType,
            'target_id' => $targetId,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'metadata' => $payload,
        ]);
    }
}
