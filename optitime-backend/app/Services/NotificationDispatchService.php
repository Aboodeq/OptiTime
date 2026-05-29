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
            ->with(['deviceTokens', 'settings'])
            ->get();

        foreach ($users as $user) {
            if (! $this->userAllowsNotificationType($user, $type)) {
                continue;
            }

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

    private function userAllowsNotificationType(User $user, string $type): bool
    {
        $settings = $user->settings;
        if (! $settings) {
            return true;
        }

        $normalizedType = strtolower(trim($type));
        $rules = [
            // Coordinator-facing announcements.
            'lecture_request_submitted' => ['push_announcements'],
            // Student/instructor urgent alerts.
            'lecture_request_reviewed' => ['push_system_alerts'],
            'grade_posted' => ['push_system_alerts'],
            // Weekly schedule changes are considered both schedule updates and system alerts.
            'schedule_updated' => ['push_system_alerts', 'email_schedule_updates'],
            'schedule_published' => ['push_system_alerts', 'email_schedule_updates'],
            // Reserved for digest jobs when implemented.
            'weekly_digest' => ['weekly_digest'],
        ];

        $requiredAny = $rules[$normalizedType] ?? null;
        if (! is_array($requiredAny) || $requiredAny === []) {
            return true;
        }

        foreach ($requiredAny as $key) {
            if ((bool) ($settings->{$key} ?? false)) {
                return true;
            }
        }

        return false;
    }
}
