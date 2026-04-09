<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSection;
use App\Models\Resource;
use App\Models\Room;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorEntityController extends Controller
{
    private function meta(string $resource): array
    {
        return match ($resource) {
            'resources' => [
                'model' => Resource::class,
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
            'rooms' => [
                'model' => Room::class,
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
                ],
            ],
            'courses' => [
                'model' => Course::class,
                'rules' => [
                    'department_id' => 'required|uuid|exists:departments,id',
                    'code' => 'required|string|max:50',
                    'name_ar' => 'required|string|max:190',
                    'name_en' => 'required|string|max:190',
                    'required_hours' => 'nullable|integer|min:1',
                    'has_lab_component' => 'boolean',
                ],
            ],
            'sections' => [
                'model' => CourseSection::class,
                'rules' => [
                    'course_id' => 'required|uuid|exists:courses,id',
                    'section_name' => 'required|string|max:120',
                    'section_type' => 'required|string|in:room,lab',
                    'capacity' => 'required|integer|min:1',
                ],
            ],
            default => abort(404),
        };
    }

    public function index(Request $request, string $resource): JsonResponse
    {
        $m = $this->meta($resource);
        $q = $m['model']::query();
        if ($resource === 'sections' && $request->filled('semester_id')) {
            $courseIds = CourseOffering::query()
                ->where('semester_id', $request->string('semester_id'))
                ->pluck('course_id');
            $q->whereIn('course_id', $courseIds);
        }

        return response()->json($q->orderBy('created_at')->get());
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $m = $this->meta($resource);
        $data = $request->validate($m['rules']);
        $row = $m['model']::query()->create($data);
        AuditLogger::log($request->user(), 'coordinator.'.$resource.'.create', $m['model'], $row->getKey(), $data, $request);

        return response()->json($row, 201);
    }

    public function show(string $resource, string $id): JsonResponse
    {
        $m = $this->meta($resource);

        return response()->json($m['model']::query()->findOrFail($id));
    }

    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        $m = $this->meta($resource);
        $row = $m['model']::query()->findOrFail($id);
        $rules = [];
        foreach ($m['rules'] as $k => $r) {
            $rules[$k] = 'sometimes|'.$r;
        }
        $data = $request->validate($rules);
        $row->update($data);
        AuditLogger::log($request->user(), 'coordinator.'.$resource.'.update', $m['model'], $id, $data, $request);

        return response()->json($row->fresh());
    }

    public function destroy(Request $request, string $resource, string $id): JsonResponse
    {
        $m = $this->meta($resource);
        $row = $m['model']::query()->findOrFail($id);
        $row->delete();
        AuditLogger::log($request->user(), 'coordinator.'.$resource.'.delete', $m['model'], $id, null, $request);

        return response()->json(['deleted' => true]);
    }
}
