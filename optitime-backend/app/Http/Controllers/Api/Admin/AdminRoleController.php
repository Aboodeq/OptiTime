<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRoleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Role::query()->with('permissions')->orderBy('code')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:roles,code',
            'name_ar' => 'required|string|max:120',
            'name_en' => 'required|string|max:120',
            'sidebar_color' => 'nullable|string|max:20',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'uuid|exists:permissions,id',
        ]);
        $permIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);
        $role = Role::query()->create($data + [
            'is_system' => false,
            'is_active' => true,
        ]);
        $role->permissions()->sync($permIds);
        AuditLogger::log($request->user(), 'roles.create', Role::class, $role->id, $data, $request);

        return response()->json($role->load('permissions'), 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(Role::query()->with('permissions')->findOrFail($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        $data = $request->validate([
            'code' => 'sometimes|string|max:50|unique:roles,code,'.$id,
            'name_ar' => 'sometimes|string|max:120',
            'name_en' => 'sometimes|string|max:120',
            'sidebar_color' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'uuid|exists:permissions,id',
        ]);
        $permIds = $data['permission_ids'] ?? null;
        unset($data['permission_ids']);
        if ($data !== []) {
            $role->update($data);
        }
        if (is_array($permIds)) {
            $role->permissions()->sync($permIds);
        }
        AuditLogger::log($request->user(), 'roles.update', Role::class, $id, $data, $request);

        return response()->json($role->fresh()->load('permissions'));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $role = Role::query()->findOrFail($id);
        if ($role->code === 'admin') {
            abort(422, 'Cannot delete admin role.');
        }
        $role->delete();
        AuditLogger::log($request->user(), 'roles.delete', Role::class, $id, null, $request);

        return response()->json(['deleted' => true]);
    }

    public function permissions(): JsonResponse
    {
        return response()->json(Permission::query()->orderBy('code')->get());
    }
}
