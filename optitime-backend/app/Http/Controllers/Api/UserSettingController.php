<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemBackup;
use App\Models\UserSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserSettingController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $this->settingsForUser($request);

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email_schedule_updates' => 'sometimes|boolean',
            'email_reminders' => 'sometimes|boolean',
            'push_announcements' => 'sometimes|boolean',
            'push_system_alerts' => 'sometimes|boolean',
            'weekly_digest' => 'sometimes|boolean',
        ]);

        $settings = $this->settingsForUser($request);
        $settings->update($data);
        AuditLogger::log($request->user(), 'user_settings.update', 'UserSetting', $settings->user_id, $data, $request);

        return response()->json($settings->refresh());
    }

    public function createBackup(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $this->settingsForUser($request)->refresh();
        $now = now();

        $payload = [
            'generatedAtIso' => $now->toIso8601String(),
            'generatedBy' => [
                'id' => (string) $user->id,
                'name' => (string) $user->full_name,
                'email' => (string) $user->email,
                'role' => (string) ($user->role?->code ?? ''),
            ],
            'userSettings' => [
                'notifications' => [
                    'emailScheduleUpdates' => (bool) $settings->email_schedule_updates,
                    'emailReminders' => (bool) $settings->email_reminders,
                    'pushAnnouncements' => (bool) $settings->push_announcements,
                    'pushSystemAlerts' => (bool) $settings->push_system_alerts,
                    'weeklyDigest' => (bool) $settings->weekly_digest,
                ],
            ],
        ];

        $backupId = (string) Str::uuid();
        $fileName = 'optitime-backup-'.$now->format('Y-m-d').'.json';

        SystemBackup::query()->create([
            'id' => $backupId,
            'initiated_by_user_id' => (string) $user->id,
            'file_name' => $fileName,
            'storage_path' => null,
            'backup_scope' => 'user_settings',
            'status' => 'completed',
            'metadata' => [
                'payload_size_bytes' => strlen((string) json_encode($payload)),
            ],
            'created_at' => $now,
        ]);

        AuditLogger::log(
            $user,
            'user_settings.backup.create',
            SystemBackup::class,
            $backupId,
            ['backup_scope' => 'user_settings', 'file_name' => $fileName],
            $request
        );

        return response()->json($payload);
    }

    private function settingsForUser(Request $request): UserSetting
    {
        return $request->user()->settings()->firstOrCreate(
            [],
            [
                'email_schedule_updates' => true,
                'email_reminders' => true,
                'push_announcements' => true,
                'push_system_alerts' => true,
                'weekly_digest' => false,
            ],
        );
    }
}
