<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            AuditLog::query()
                ->with('actor:id,full_name,email')
                ->orderByDesc('created_at')
                ->paginate($request->integer('per_page', 30))
        );
    }
}
