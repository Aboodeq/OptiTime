<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            AppNotification::query()
                ->where('user_id', $request->user()->id)
                ->orderByDesc('created_at')
                ->paginate(20)
        );
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $n = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();
        $n->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['read' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $items = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($items);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail()
            ->delete();

        return response()->json(['deleted' => true]);
    }
}
