<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Instructor;
use App\Models\Role;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminInstructorController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Instructor::query()
            ->with(['user.role', 'specialization'])
            ->orderBy('created_at')
            ->get();

        return response()->json($rows->map(fn (Instructor $i) => $this->toResource($i))->values()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'boolean',
            'specialization_id' => 'required|uuid|exists:specializations,id',
            'min_work_hours_per_week' => 'required|integer|min:0',
            'max_work_hours_per_week' => 'required|integer|min:1',
        ]);
        if ($data['min_work_hours_per_week'] > $data['max_work_hours_per_week']) {
            return response()->json([
                'message' => 'The min work hours must be less than or equal to max work hours.',
                'errors' => ['min_work_hours_per_week' => ['invalid range']],
            ], 422);
        }

        $roleId = Role::query()->where('code', 'instructor')->value('id');
        abort_if(! $roleId, 500, 'Instructor role is not configured.');

        $instructor = DB::transaction(function () use ($data, $roleId, $request) {
            $user = User::query()->create([
                'role_id' => $roleId,
                'department_id' => $data['department_id'] ?? null,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);
            UserSetting::query()->create(['user_id' => $user->id]);

            $ins = Instructor::query()->create([
                'user_id' => $user->id,
                'specialization_id' => $data['specialization_id'],
                'min_work_hours_per_week' => $data['min_work_hours_per_week'],
                'max_work_hours_per_week' => $data['max_work_hours_per_week'],
            ]);

            AuditLogger::log($request->user(), 'instructors.create', Instructor::class, $ins->id, ['email' => $user->email], $request);

            return $ins->load(['user.role', 'specialization']);
        });

        return response()->json($this->toResource($instructor), 201);
    }

    public function show(string $id): JsonResponse
    {
        $instructor = Instructor::query()
            ->with(['user.role', 'specialization'])
            ->findOrFail($id);

        return response()->json($this->toResource($instructor));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $instructor = Instructor::query()->with('user')->findOrFail($id);
        $user = $instructor->user;
        abort_if($user->role?->code !== 'instructor', 404);

        $data = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'sometimes|boolean',
            'specialization_id' => 'sometimes|uuid|exists:specializations,id',
            'min_work_hours_per_week' => 'sometimes|integer|min:0',
            'max_work_hours_per_week' => 'sometimes|integer|min:1',
        ]);

        $min = $data['min_work_hours_per_week'] ?? $instructor->min_work_hours_per_week;
        $max = $data['max_work_hours_per_week'] ?? $instructor->max_work_hours_per_week;
        if ($min > $max) {
            return response()->json([
                'message' => 'The min work hours must be less than or equal to max work hours.',
                'errors' => ['min_work_hours_per_week' => ['invalid range']],
            ], 422);
        }

        DB::transaction(function () use ($data, $user, $instructor, $request) {
            $userData = [];
            if (isset($data['full_name'])) {
                $userData['full_name'] = $data['full_name'];
            }
            if (isset($data['email'])) {
                $userData['email'] = $data['email'];
            }
            if (isset($data['password'])) {
                $userData['password_hash'] = Hash::make($data['password']);
                $userData['password_updated_at'] = now();
            }
            if (array_key_exists('department_id', $data)) {
                $userData['department_id'] = $data['department_id'];
            }
            if (isset($data['is_active'])) {
                $userData['is_active'] = $data['is_active'];
            }
            if ($userData !== []) {
                $user->update($userData);
            }

            $profile = [];
            if (isset($data['specialization_id'])) {
                $profile['specialization_id'] = $data['specialization_id'];
            }
            if (isset($data['min_work_hours_per_week'])) {
                $profile['min_work_hours_per_week'] = $data['min_work_hours_per_week'];
            }
            if (isset($data['max_work_hours_per_week'])) {
                $profile['max_work_hours_per_week'] = $data['max_work_hours_per_week'];
            }
            if ($profile !== []) {
                $instructor->update($profile);
            }

            AuditLogger::log($request->user(), 'instructors.update', Instructor::class, $instructor->id, $data, $request);
        });

        $fresh = $instructor->fresh()->load(['user.role', 'specialization']);

        return response()->json($this->toResource($fresh));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $instructor = Instructor::query()->with('user')->findOrFail($id);
        abort_if($instructor->user?->role?->code !== 'instructor', 404);

        $userId = $instructor->user_id;
        $instructor->user->delete();
        AuditLogger::log($request->user(), 'instructors.delete', Instructor::class, $id, ['user_id' => $userId], $request);

        return response()->json(['deleted' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(Instructor $instructor): array
    {
        $user = $instructor->user;

        return [
            'id' => $instructor->id,
            'user_id' => $user->id,
            'name' => $user->full_name,
            'email' => $user->email,
            'department_id' => $user->department_id,
            'specialization_id' => $instructor->specialization_id,
            'min_work_hours_per_week' => $instructor->min_work_hours_per_week,
            'max_work_hours_per_week' => $instructor->max_work_hours_per_week,
            'is_active' => $user->is_active,
        ];
    }
}
