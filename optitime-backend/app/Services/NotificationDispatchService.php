<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Support\Str;

final class NotificationDispatchService
{
    public function __construct(
        private FcmClient $fcmClient
    ) {}

    /**
     * @param  array<int, string>  $userIds
     */
    public function notifyUsers(
        array $userIds,
        string $title,
        string $message,
        string $type,
        string $priority = 'medium',
        array $payload = []
    ): void {
        $ids = array_values(array_unique(array_filter($userIds, fn ($v) => is_string($v) && $v !== '')));
        if ($ids === []) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $ids)
            ->with('deviceTokens')
            ->get();

        foreach ($users as $user) {
            $notificationId = 'notif_'.Str::uuid()->toString();
            AppNotification::query()->create([
                'id' => $notificationId,
                'user_id' => (string) $user->id,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'priority' => $priority,
                'payload' => $payload,
                'is_read' => false,
                'created_at' => now(),
            ]);

            foreach ($user->deviceTokens as $token) {
                $this->fcmClient->sendToToken(
                    (string) $token->fcm_token,
                    $title,
                    $message,
                    $payload + ['notification_type' => $type, 'notification_id' => $notificationId, 'priority' => $priority]
                );
            }
        }
    }
}
