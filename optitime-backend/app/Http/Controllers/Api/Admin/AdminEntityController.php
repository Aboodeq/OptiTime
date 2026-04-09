<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseConstraint;
use App\Models\CoursePrerequisite;
use App\Models\CourseSection;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Resource;
use App\Models\Room;
use App\Models\ScheduleSetting;
use App\Models\Semester;
use App\Models\Specialization;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AdminEntityController extends Controller
{
    /**
     * Admin routes use ->defaults('resource', $name); that value must be read here.
     * Relying on method injection for `string $resource` can yield an empty value (e.g. name clash
     * with the Resource model), which triggers "Unknown resource".
     */
    private function resourceKey(Request $request): string
    {
        $key = $request->route()?->parameter('resource');
        if (is_string($key) && $key !== '') {
            return $key;
        }
        $path = '/'.$request->path();
        if (preg_match('#/admin/([^/]+)#', $path, $m)) {
            return $m[1];
        }

        abort(404, 'Unknown resource');
    }

    private function meta(string $resource): array
    {
        return match ($resource) {
            'faculties' => [
                'model' => Faculty::class,
                'perm' => 'faculties',
                'rules' => [
                    'code' => 'required|string|max:50',
                    'name_ar' => 'required|string|max:150',
                    'name_en' => 'required|string|max:150',
                    'graduation_hours' => 'nullable|integer|min:0',
                    'studying_level' => 'nullable|integer|min:1',
                    'color' => 'nullable|string|max:20',
                    'icon_url' => 'nullable|string',
                    'is_active' => 'boolean',
                ],
            ],
            'departments' => [
                'model' => Department::class,
                'perm' => 'departments',
                'rules' => [
                    'faculty_id' => 'required|uuid|exists:faculties,id',
                    'code' => 'required|string|max:50',
                    'name_ar' => 'required|string|max:150',
                    'name_en' => 'required|string|max:150',
                    'icon_url' => 'nullable|string',
                    'is_active' => 'boolean',
                ],
            ],
            'specializations' => [
                'model' => Specialization::class,
                'perm' => 'specializations',
                'rules' => [
                    'code' => 'required|string|max:50',
                    'name_ar' => 'required|string|max:150',
                    'name_en' => 'required|string|max:150',
                    'is_active' => 'boolean',
                ],
            ],
            'semesters' => [
                'model' => Semester::class,
                'perm' => 'semesters',
                'rules' => [
                    'code' => 'required|string|max:50',
                    'name' => 'required|string|max:150',
                    'academic_year' => 'required|string|max:20',
                    'start_date' => 'required|date',
                    'end_date' => 'required|date|after_or_equal:start_date',
                    'is_active' => 'boolean',
                ],
            ],
            'rooms' => [
                'model' => Room::class,
                'perm' => 'buildings',
                'rules' => [
                    'name_ar' => 'required|string|max:190',
                    'name_en' => 'required|string|max:190',
                    'type' => 'required|string|in:class,lab,hall',
                    'capacity' => 'required|integer|min:1',
                    'location_ar' => 'nullable|string|max:190',
                    'location_en' => 'nullable|string|max:190',
                    'status' => 'nullable|string|max:30',
                    'notes_ar' => 'nullable|string',
                    'notes_en' => 'nullable|string',
                    'resource_ids' => 'nullable|array',
                    'resource_ids.*' => 'uuid|exists:resources,id',
                ],
            ],
            'resources' => [
                'model' => Resource::class,
                'perm' => 'resources',
                'rules' => [
                    'name_ar' => 'required|string|max:190',
                    'name_en' => 'required|string|max:190',
                    'type' => 'required|string|max:80',
                    'quantity' => 'required|integer|min:0',
                    'location_ar' => 'nullable|string|max:190',
                    'location_en' => 'nullable|string|max:190',
                    'status' => 'nullable|string|max:30',
                    'notes_ar' => 'nullable|string',
                    'notes_en' => 'nullable|string',
                ],
            ],
            'courses' => [
                'model' => Course::class,
                'perm' => 'programs',
                'rules' => [
                    'department_id' => 'required|uuid|exists:departments,id',
                    'code' => 'required|string|max:50',
                    'name_ar' => 'required|string|max:190',
                    'name_en' => 'required|string|max:190',
                    'required_hours' => 'nullable|integer|min:1',
                    'room_consumed_hours' => 'nullable|integer|min:0',
                    'lab_consumed_hours' => 'nullable|integer|min:0',
                    'has_lab_component' => 'boolean',
                ],
            ],
            'sections' => [
                'model' => CourseSection::class,
                'perm' => 'programs',
                'rules' => [
                    'course_id' => 'required|uuid|exists:courses,id',
                    'section_name' => 'required|string|max:120',
                    'section_type' => 'required|string|in:room,lab',
                    'capacity' => 'required|integer|min:1',
                ],
            ],
            'schedule-settings' => [
                'model' => ScheduleSetting::class,
                'perm' => 'schedule_settings',
                'rules' => [
                    'day_start' => 'required|date_format:H:i',
                    'day_end' => 'required|date_format:H:i',
                    'slot_minutes' => 'required|integer|min:15|max:240',
                    'gap_minutes' => 'required|integer|min:0|max:120',
                    'max_daily_lectures' => 'required|integer|min:1|max:12',
                    'capacity_threshold' => 'required|integer|min:0|max:100',
                ],
            ],
            default => abort(404, 'Unknown resource'),
        };
    }

    public function index(Request $request): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $m = $this->meta($resource);

        if ($resource === 'rooms') {
            $rows = Room::query()
                ->with('resources')
                ->orderBy('created_at')
                ->get()
                ->map(fn (Room $room) => $this->formatRoomResponse($room));

            return response()->json($rows->values()->all());
        }

        if ($resource === 'courses') {
            return response()->json(
                Course::query()
                    ->with(['sections.instructors', 'courseConstraint', 'coursePrerequisites'])
                    ->orderBy('created_at')
                    ->get()
            );
        }

        if ($resource === 'sections') {
            return response()->json(
                CourseSection::query()->with('instructors')->orderBy('created_at')->get()
            );
        }

        $q = $m['model']::query();

        return response()->json($q->orderBy('created_at')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $m = $this->meta($resource);

        if ($resource === 'courses') {
            $rules = array_merge($m['rules'], $this->courseExtraRules(false));
            $data = $request->validate($rules);
            $this->assertCourseLevelsValid($data);
            $extras = Arr::only($data, $this->courseExtraFieldKeys());
            $core = Arr::except($data, $this->courseExtraFieldKeys());
            /** @var Course $row */
            $row = Course::query()->create($core);
            $this->syncCourseExtras($row, $extras, true, true);
            AuditLogger::log($request->user(), $resource.'.create', $m['model'], $row->getKey(), $data, $request);

            return response()->json($this->freshCourseWithRelations($row), 201);
        }

        $data = $request->validate($m['rules']);

        if ($resource === 'rooms') {
            $resourceIds = $data['resource_ids'] ?? [];
            unset($data['resource_ids']);
            /** @var Room $row */
            $row = Room::query()->create($data);
            $row->resources()->sync(is_array($resourceIds) ? $resourceIds : []);
            AuditLogger::log(
                $request->user(),
                $resource.'.create',
                $m['model'],
                $row->getKey(),
                array_merge($data, ['resource_ids' => is_array($resourceIds) ? $resourceIds : []]),
                $request,
            );

            return response()->json($this->formatRoomResponse($row->fresh()), 201);
        }

        $row = $m['model']::query()->create($data);
        AuditLogger::log($request->user(), $resource.'.create', $m['model'], $row->getKey(), $data, $request);

        if ($resource === 'sections') {
            /** @var CourseSection $row */
            return response()->json($row->fresh('instructors'), 201);
        }

        return response()->json($row, 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $m = $this->meta($resource);

        if ($resource === 'rooms') {
            $row = Room::query()->with('resources')->findOrFail($id);

            return response()->json($this->formatRoomResponse($row));
        }

        if ($resource === 'courses') {
            return response()->json(
                Course::query()
                    ->with(['sections.instructors', 'courseConstraint', 'coursePrerequisites'])
                    ->findOrFail($id)
            );
        }
        if ($resource === 'sections') {
            return response()->json(CourseSection::query()->with('instructors')->findOrFail($id));
        }

        $row = $m['model']::query()->findOrFail($id);

        return response()->json($row);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $m = $this->meta($resource);
        $row = $m['model']::query()->findOrFail($id);

        if ($resource === 'courses') {
            $rules = [];
            foreach ($m['rules'] as $k => $r) {
                $rules[$k] = 'sometimes|'.$r;
            }
            foreach ($this->courseExtraRules(true) as $k => $r) {
                $rules[$k] = $r;
            }
            $data = $request->validate($rules);
            $this->assertCourseLevelsValid($data);
            /** @var Course $row */
            $row = Course::query()->findOrFail($id);
            $extras = Arr::only($data, $this->courseExtraFieldKeys());
            $core = Arr::except($data, $this->courseExtraFieldKeys());
            if ($core !== []) {
                $row->update($core);
            }
            $levelsPresent = array_key_exists('min_student_year_level', $data) || array_key_exists('max_student_year_level', $data);
            $prereqPresent = array_key_exists('prerequisite_course_ids', $data);
            if ($levelsPresent || $prereqPresent) {
                $this->syncCourseExtras($row, $extras, $levelsPresent, $prereqPresent);
            }
            AuditLogger::log($request->user(), $resource.'.update', $m['model'], $id, $data, $request);

            return response()->json($this->freshCourseWithRelations($row));
        }

        $rules = [];
        foreach ($m['rules'] as $k => $r) {
            $rules[$k] = 'sometimes|'.$r;
        }
        $data = $request->validate($rules);

        if ($resource === 'rooms') {
            /** @var Room $row */
            $auditPayload = $data;
            if ($request->has('resource_ids')) {
                $ids = $data['resource_ids'] ?? [];
                unset($data['resource_ids']);
                $row->resources()->sync(is_array($ids) ? $ids : []);
            } else {
                unset($data['resource_ids']);
            }
            if ($data !== []) {
                $row->update($data);
            }
            AuditLogger::log($request->user(), $resource.'.update', $m['model'], $id, $auditPayload, $request);

            return response()->json($this->formatRoomResponse($row->fresh()));
        }

        $row->update($data);
        AuditLogger::log($request->user(), $resource.'.update', $m['model'], $id, $data, $request);

        if ($resource === 'sections') {
            /** @var CourseSection $row */
            return response()->json($row->fresh('instructors'));
        }

        return response()->json($row->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $resource = $this->resourceKey($request);
        $m = $this->meta($resource);
        $row = $m['model']::query()->findOrFail($id);
        $row->delete();
        AuditLogger::log($request->user(), $resource.'.delete', $m['model'], $id, null, $request);

        return response()->json(['deleted' => true]);
    }

    /**
     * @return list<string>
     */
    private function courseExtraFieldKeys(): array
    {
        return ['min_student_year_level', 'max_student_year_level', 'prerequisite_course_ids'];
    }

    /**
     * @return array<string, string>
     */
    private function courseExtraRules(bool $forUpdate): array
    {
        $p = $forUpdate ? 'sometimes|' : '';

        return [
            'min_student_year_level' => $p.'nullable|integer|min:1',
            'max_student_year_level' => $p.'nullable|integer|min:1',
            'prerequisite_course_ids' => $p.'nullable|array',
            'prerequisite_course_ids.*' => 'uuid|exists:courses,id',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCourseLevelsValid(array $data): void
    {
        $min = $data['min_student_year_level'] ?? null;
        $max = $data['max_student_year_level'] ?? null;
        if ($min !== null && $max !== null && $min > $max) {
            throw ValidationException::withMessages([
                'max_student_year_level' => ['Must be greater than or equal to min_student_year_level.'],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    private function syncCourseExtras(Course $course, array $extras, bool $levelsPresent, bool $prereqPresent): void
    {
        if ($levelsPresent) {
            CourseConstraint::query()->updateOrCreate(
                ['course_id' => $course->id],
                [
                    'min_student_year_level' => $extras['min_student_year_level'] ?? null,
                    'max_student_year_level' => $extras['max_student_year_level'] ?? null,
                ]
            );
        }

        if ($prereqPresent) {
            $ids = $extras['prerequisite_course_ids'] ?? [];
            if (! is_array($ids)) {
                $ids = [];
            }
            CoursePrerequisite::query()->where('course_id', $course->id)->delete();
            foreach (array_unique($ids) as $pid) {
                if (! is_string($pid) || $pid === $course->id) {
                    continue;
                }
                CoursePrerequisite::query()->create([
                    'course_id' => $course->id,
                    'prerequisite_course_id' => $pid,
                ]);
            }
        }
    }

    private function freshCourseWithRelations(Course $course): Course
    {
        return Course::query()
            ->with(['sections.instructors', 'courseConstraint', 'coursePrerequisites'])
            ->findOrFail($course->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatRoomResponse(Room $room): array
    {
        $room->loadMissing('resources');
        $base = $room->toArray();
        unset($base['resources']);
        $base['resource_ids'] = $room->resources->pluck('id')->values()->all();

        return $base;
    }
}
