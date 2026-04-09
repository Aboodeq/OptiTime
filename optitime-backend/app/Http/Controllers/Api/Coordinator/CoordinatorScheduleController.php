<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncScheduleSessionsRequest;
use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use App\Scheduling\DayMapping;
use App\Services\AuditLogger;
use App\Services\ScheduleGenerateService;
use App\Services\ScheduleSessionStudentAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                ->with([
                    'sessions.room',
                    'sessions.sectionInstructor.section.course',
                    'sessions.courseOffering.course',
                    'sessions.sessionStudents.student.user',
                ])
                ->findOrFail($id)
        );
    }

    public function syncSessions(SyncScheduleSessionsRequest $request, string $id, ScheduleSessionStudentAssignmentService $sessionStudentAssignment): JsonResponse
    {
        $plan = SemesterSchedulePlan::query()->findOrFail($id);
        if ($plan->status === 'published') {
            return response()->json([
                'message' => 'Cannot replace sessions on a published plan; create a draft plan instead.',
            ], 422);
        }

        $sessionsIn = $request->validated()['sessions'];
        $semesterId = $plan->semester_id;

        $errors = [];
        $normalized = [];

        foreach ($sessionsIn as $index => $row) {
            $dayShort = DayMapping::toShort($row['day']);
            $start = $this->normalizeTimeString($row['start']);
            $end = $this->normalizeTimeString($row['end']);
            if ($start >= $end) {
                $errors[] = "sessions.{$index}: start_time must be before end_time";

                continue;
            }

            $offering = CourseOffering::query()->find($row['course_offering_id']);
            if (! $offering || $offering->semester_id !== $semesterId) {
                $errors[] = "sessions.{$index}: course_offering_id must belong to this plan semester";

                continue;
            }

            $csi = CourseSectionInstructor::query()->with('section')->find($row['section_instructor_id']);
            if (! $csi || ! $csi->section) {
                $errors[] = "sessions.{$index}: invalid section_instructor_id";

                continue;
            }
            if ($csi->section->course_id !== $offering->course_id) {
                $errors[] = "sessions.{$index}: section instructor does not match course offering course";

                continue;
            }

            $normalized[] = [
                'room_id' => $row['room_id'],
                'section_instructor_id' => $row['section_instructor_id'],
                'course_offering_id' => $row['course_offering_id'],
                'day_value' => $dayShort,
                'start_time' => $start,
                'end_time' => $end,
                'instructor_id' => $csi->instructor_id,
            ];
        }

        if ($errors !== []) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        $overlapErr = $this->detectScheduleOverlaps($normalized);
        if ($overlapErr !== null) {
            return response()->json(['message' => $overlapErr, 'errors' => [$overlapErr]], 422);
        }

        DB::transaction(function () use ($plan, $normalized): void {
            ScheduleSession::query()->where('schedule_plan_id', $plan->id)->delete();
            foreach ($normalized as $s) {
                ScheduleSession::query()->create([
                    'schedule_plan_id' => $plan->id,
                    'room_id' => $s['room_id'],
                    'section_instructor_id' => $s['section_instructor_id'],
                    'course_offering_id' => $s['course_offering_id'],
                    'day_value' => $s['day_value'],
                    'start_time' => $s['start_time'],
                    'end_time' => $s['end_time'],
                ]);
            }
        });

        $sessionStudentAssignment->syncForPlan((string) $plan->id);

        AuditLogger::log($request->user(), 'schedules.update', SemesterSchedulePlan::class, $plan->id, ['sessions_synced' => count($normalized)], $request);

        return response()->json(
            SemesterSchedulePlan::query()
                ->with([
                    'sessions.room',
                    'sessions.sectionInstructor.section.course',
                    'sessions.courseOffering.course',
                    'sessions.sessionStudents.student.user',
                ])
                ->findOrFail($plan->id)
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

    public function publishFromGeneration(Request $request, ScheduleGenerateService $generator, ScheduleSessionStudentAssignmentService $sessionStudentAssignment): JsonResponse
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

        $sessionStudentAssignment->syncForPlan((string) $plan->id);

        AuditLogger::log($request->user(), 'schedules.publish', SemesterSchedulePlan::class, $plan->id, ['items' => count($result['sessions'])], $request);

        return response()->json([
            'schedule_plan' => $plan->load('sessions'),
            'meta' => $result['meta'] ?? [],
        ], 201);
    }

    private function normalizeTimeString(string $value): string
    {
        $t = trim($value);
        if (strlen($t) === 5 && preg_match('/^\d{2}:\d{2}$/', $t)) {
            return $t.':00';
        }

        return $t;
    }

    /**
     * @param  array<int, array{day_value: string, start_time: string, end_time: string, room_id: string, instructor_id: string}>  $normalized
     */
    private function detectScheduleOverlaps(array $normalized): ?string
    {
        $toMin = function (string $time): int {
            $parts = explode(':', substr($time, 0, 8));

            return ((int) $parts[0]) * 60 + ((int) ($parts[1] ?? 0));
        };

        $byRoomDay = [];
        $byInstDay = [];
        foreach ($normalized as $s) {
            $rs = $toMin($s['start_time']);
            $re = $toMin($s['end_time']);
            $rk = $s['room_id'].'|'.$s['day_value'];
            $ik = $s['instructor_id'].'|'.$s['day_value'];
            $byRoomDay[$rk][] = [$rs, $re];
            $byInstDay[$ik][] = [$rs, $re];
        }

        foreach ([$byRoomDay, $byInstDay] as $groups) {
            foreach ($groups as $intervals) {
                usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
                for ($i = 1; $i < count($intervals); $i++) {
                    if ($intervals[$i][0] < $intervals[$i - 1][1]) {
                        return 'Schedule has overlapping room or instructor assignments.';
                    }
                }
            }
        }

        return null;
    }
}
