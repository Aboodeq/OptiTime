<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationDeviceController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|min:20',
            'device_label' => 'nullable|string|max:80',
            'platform' => 'nullable|string|max:30',
        ]);

        $row = UserDeviceToken::query()->updateOrCreate(
            [
                'user_id' => (string) $request->user()->id,
                'fcm_token_sha256' => hash('sha256', $data['fcm_token']),
            ],
            [
                'fcm_token' => $data['fcm_token'],
                'device_label' => $data['device_label'] ?? null,
                'platform' => $data['platform'] ?? null,
                'last_seen_at' => now(),
            ]
        );

        return response()->json($row, 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => 'required|string|min:20',
        ]);

        UserDeviceToken::query()
            ->where('user_id', (string) $request->user()->id)
            ->where('fcm_token_sha256', hash('sha256', $data['fcm_token']))
            ->delete();

        return response()->json(['deleted' => true]);
    }
}
