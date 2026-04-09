<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($request->user()->load(['role', 'instructor', 'student']));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|email|unique:users,email,'.$request->user()->id,
            'avatar_url' => 'sometimes|nullable|string',
        ]);
        $request->user()->update($data);
        AuditLogger::log($request->user(), 'profile.update', 'User', $request->user()->id, $data, $request);

        return response()->json($request->user()->fresh()->load(['role', 'instructor', 'student']));
    }
}
