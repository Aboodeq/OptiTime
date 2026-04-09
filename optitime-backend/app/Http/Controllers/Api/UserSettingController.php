<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
