<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSetting;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminStudentController extends Controller
{
    private const STUDY_STATUSES = ['regular', 'suspended', 'graduated'];

    public function index(): JsonResponse
    {
        $rows = Student::query()
            ->with(['user.role'])
            ->orderBy('created_at')
            ->get();

        return response()->json($rows->map(fn (Student $s) => $this->toResource($s))->values()->all());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:150',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'boolean',
            'university_number' => 'nullable|string|max:40|unique:students,university_number',
            'completed_hours' => 'required|integer|min:0',
            'year_level' => 'required|integer|min:1|max:6',
            'study_status' => 'required|string|in:'.implode(',', self::STUDY_STATUSES),
        ]);

        $roleId = Role::query()->where('code', 'student')->value('id');
        abort_if(! $roleId, 500, 'Student role is not configured.');

        $universityNumber = $data['university_number'] ?? null;
        if ($universityNumber === null || $universityNumber === '') {
            $universityNumber = $this->generateUniqueUniversityNumber();
        }

        $student = DB::transaction(function () use ($data, $roleId, $universityNumber, $request) {
            $user = User::query()->create([
                'role_id' => $roleId,
                'department_id' => $data['department_id'] ?? null,
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'is_active' => $data['is_active'] ?? true,
            ]);
            UserSetting::query()->create(['user_id' => $user->id]);

            $stu = Student::query()->create([
                'user_id' => $user->id,
                'university_number' => $universityNumber,
                'completed_hours' => $data['completed_hours'],
                'year_level' => $data['year_level'],
                'study_status' => $data['study_status'],
            ]);

            AuditLogger::log($request->user(), 'students.create', Student::class, $stu->user_id, ['email' => $user->email], $request);

            return $stu->load(['user.role']);
        });

        return response()->json($this->toResource($student), 201);
    }

    public function show(string $id): JsonResponse
    {
        $student = Student::query()
            ->with(['user.role'])
            ->where('user_id', $id)
            ->firstOrFail();

        return response()->json($this->toResource($student));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $student = Student::query()->with('user')->where('user_id', $id)->firstOrFail();
        $user = $student->user;
        abort_if($user->role?->code !== 'student', 404);

        $data = $request->validate([
            'full_name' => 'sometimes|string|max:150',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'password' => 'sometimes|string|min:8',
            'department_id' => 'nullable|uuid|exists:departments,id',
            'is_active' => 'sometimes|boolean',
            'university_number' => [
                'sometimes',
                'string',
                'max:40',
                Rule::unique('students', 'university_number')->ignore($student->user_id, 'user_id'),
            ],
            'completed_hours' => 'sometimes|integer|min:0',
            'year_level' => 'sometimes|integer|min:1|max:6',
            'study_status' => 'sometimes|string|in:'.implode(',', self::STUDY_STATUSES),
        ]);

        DB::transaction(function () use ($data, $user, $student, $request) {
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
            if (isset($data['university_number'])) {
                $profile['university_number'] = $data['university_number'];
            }
            if (isset($data['completed_hours'])) {
                $profile['completed_hours'] = $data['completed_hours'];
            }
            if (isset($data['year_level'])) {
                $profile['year_level'] = $data['year_level'];
            }
            if (isset($data['study_status'])) {
                $profile['study_status'] = $data['study_status'];
            }
            if ($profile !== []) {
                $student->update($profile);
            }

            AuditLogger::log($request->user(), 'students.update', Student::class, $student->user_id, $data, $request);
        });

        $fresh = $student->fresh()->load(['user.role']);

        return response()->json($this->toResource($fresh));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $student = Student::query()->with('user')->where('user_id', $id)->firstOrFail();
        abort_if($student->user?->role?->code !== 'student', 404);

        $student->user->delete();
        AuditLogger::log($request->user(), 'students.delete', Student::class, $id, null, $request);

        return response()->json(['deleted' => true]);
    }

    private function generateUniqueUniversityNumber(): string
    {
        $year = (int) date('Y');
        for ($i = 0; $i < 20; $i++) {
            $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $candidate = 'UNI-'.$year.$suffix;
            if (! Student::query()->where('university_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'UNI-'.$year.uniqid();
    }

    /**
     * @return array<string, mixed>
     */
    private function toResource(Student $student): array
    {
        $user = $student->user;

        return [
            'id' => $student->user_id,
            'user_id' => $student->user_id,
            'name' => $user->full_name,
            'email' => $user->email,
            'department_id' => $user->department_id,
            'university_number' => $student->university_number,
            'completed_hours' => $student->completed_hours,
            'year_level' => $student->year_level,
            'study_status' => $student->study_status,
            'is_active' => $user->is_active,
        ];
    }
}
