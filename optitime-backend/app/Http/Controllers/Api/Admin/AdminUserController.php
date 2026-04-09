<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::query()->with('role')->orderBy('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|uuid|exists:roles,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'boolean',
        ]);
        $data['password_hash'] = Hash::make($data['password']);
        unset($data['password']);
        $data['is_active'] = $data['is_active'] ?? true;
        $user = User::query()->create($data);
        UserSetting::query()->create(['user_id' => $user->id]);
        AuditLogger::log($request->user(), 'users.create', User::class, $user->id, ['email' => $user->email], $request);

        return response()->json($user->load('role'), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(User::query()->with('role')->findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $data = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|email|unique:users,email,'.$id,
            'password' => 'sometimes|string|min:8',
            'role_id' => 'sometimes|uuid|exists:roles,id',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'sometimes|boolean',
        ]);
        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            $data['password_updated_at'] = now();
            unset($data['password']);
        }
        $user->update($data);
        AuditLogger::log($request->user(), 'users.update', User::class, $id, $data, $request);

        return response()->json($user->fresh()->load('role'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = User::query()->findOrFail($id);
        $user->delete();
        AuditLogger::log($request->user(), 'users.delete', User::class, $id, null, $request);

        return response()->json(['deleted' => true]);
    }
}
