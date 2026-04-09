<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use App\Services\AuditLogger;
use App\Services\ScheduleGenerateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorScheduleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = SemesterSchedulePlan::query()->withCount('sessions')->orderByDesc('created_at');
        if ($request->filled('semester_id')) {
            $q->where('semester_id', $request->string('semester_id'));
        }

        return response()->json($q->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'semester_id' => 'required|uuid|exists:semesters,id',
            'status' => 'nullable|string|max:32',
            'notes' => 'nullable|string',
        ]);
        $data['status'] = $data['status'] ?? 'draft';
        $row = SemesterSchedulePlan::query()->create($data);
        AuditLogger::log($request->user(), 'schedules.create', SemesterSchedulePlan::class, $row->id, $data, $request);

        return response()->json($row, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(
            SemesterSchedulePlan::query()
                ->with(['sessions.room', 'sessions.sectionInstructor.section.course', 'sessions.courseOffering'])
                ->findOrFail($id)
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $row = SemesterSchedulePlan::query()->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|string|max:32',
            'notes' => 'sometimes|nullable|string',
        ]);
        $row->update($data);
        AuditLogger::log($request->user(), 'schedules.update', SemesterSchedulePlan::class, $id, $data, $request);

        return response()->json($row->fresh());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $row = SemesterSchedulePlan::query()->findOrFail($id);
        $row->delete();
        AuditLogger::log($request->user(), 'schedules.delete', SemesterSchedulePlan::class, $id, null, $request);

        return response()->json(['deleted' => true]);
    }

    public function publishFromGeneration(Request $request, ScheduleGenerateService $generator): JsonResponse
    {
        $request->validate([
            'algorithm' => 'required|in:genetic,backtracking',
            'semester_id' => 'required|uuid|exists:semesters,id',
            'schedule_settings' => 'nullable|array',
            'settings_id' => 'nullable|uuid|exists:schedule_settings,id',
            'baseDraft' => 'nullable|array',
            'notes' => 'nullable|string',
            'seed' => 'nullable|integer',
            'instructor_availabilities' => 'nullable|array',
            'instructor_availabilities.*.instructor_id' => 'required|uuid',
            'instructor_availabilities.*.day_of_week' => 'required|string|max:32',
            'instructor_availabilities.*.start' => 'required|string|max:8',
            'instructor_availabilities.*.end' => 'required|string|max:8',
        ]);

        $result = $generator->generate(
            $request->string('algorithm')->toString(),
            $request->string('semester_id')->toString(),
            $request->input('schedule_settings'),
            $request->string('settings_id')->toString() ?: null,
            $request->input('baseDraft'),
            null,
            $request->integer('seed') ?: null,
            $request->input('instructor_availabilities'),
        );

        if (! $result['success'] || empty($result['sessions'])) {
            return response()->json($result, 422);
        }

        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $request->string('semester_id')->toString(),
            'status' => 'published',
            'selected_algorithm' => $request->string('algorithm')->toString(),
            'generated_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        foreach ($result['sessions'] as $s) {
            ScheduleSession::query()->create([
                'schedule_plan_id' => $plan->id,
                'room_id' => $s['room_id'],
                'section_instructor_id' => $s['section_instructor_id'],
                'course_offering_id' => $s['course_offering_id'],
                'day_value' => $s['day'],
                'start_time' => $s['start'],
                'end_time' => $s['end'],
            ]);
        }

        AuditLogger::log($request->user(), 'schedules.publish', SemesterSchedulePlan::class, $plan->id, ['items' => count($result['sessions'])], $request);

        return response()->json([
            'schedule_plan' => $plan->load('sessions'),
            'meta' => $result['meta'] ?? [],
        ], 201);
    }
}
