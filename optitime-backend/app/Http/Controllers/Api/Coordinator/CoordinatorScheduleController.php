<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\SyncScheduleSessionsRequest;
use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\SemesterSchedulePlan;
use App\Scheduling\DayMapping;
use App\Services\AuditLogger;
use App\Services\NotificationDispatchService;
use App\Services\ScheduleGenerateService;
use App\Services\ScheduleSessionStudentAssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function syncSessions(
        SyncScheduleSessionsRequest $request,
        string $id,
        ScheduleSessionStudentAssignmentService $sessionStudentAssignment,
        ScheduleGenerateService $generator
    ): JsonResponse
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

        $preFinalValidation = $generator->validateSessionsBeforePublish(
            (string) $semesterId,
            $sessionsIn,
            null,
            null,
            null,
            false
        );
        if (! $preFinalValidation['ok']) {
            return response()->json([
                'message' => 'Final hard-constraint validation failed.',
                'errors' => $preFinalValidation['errors'],
                'meta' => [
                    'hard_violations' => $preFinalValidation['hard_count'],
                    'hard_breakdown' => $preFinalValidation['hard_breakdown'],
                ],
            ], 422);
        }

        try {
            DB::transaction(function () use ($plan, $normalized, $sessionStudentAssignment): void {
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

                $sessionStudentAssignment->syncForPlan((string) $plan->id);
                $postErrors = $this->postAssignmentValidationErrors(
                    (string) $plan->id,
                    $sessionStudentAssignment,
                    false
                );
                if ($postErrors !== []) {
                    throw ValidationException::withMessages($postErrors);
                }
            });
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Final validation failed after student assignment.',
                'errors' => $this->flattenValidationErrors($e),
            ], 422);
        }

        $shouldNotify = $request->has('notify')
            ? $request->boolean('notify')
            : strtolower(trim((string) $plan->status)) === 'published';
        if ($shouldNotify) {
            $this->notifyPlanStakeholders(
                (string) $plan->id,
                'Schedule updated',
                'Your weekly schedule has been updated. Tap to open it.',
                'schedule_updated'
            );
        }

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

    public function update(
        Request $request,
        string $id,
        ScheduleGenerateService $generator,
        ScheduleSessionStudentAssignmentService $sessionStudentAssignment
    ): JsonResponse
    {
        $row = SemesterSchedulePlan::query()->findOrFail($id);
        $data = $request->validate([
            'status' => 'sometimes|string|max:32',
            'notes' => 'sometimes|nullable|string',
        ]);

        $nextStatus = array_key_exists('status', $data)
            ? strtolower(trim((string) $data['status']))
            : strtolower(trim((string) $row->status));
        $publishingNow = $nextStatus === 'published' && strtolower(trim((string) $row->status)) !== 'published';

        if ($publishingNow) {
            $sessions = ScheduleSession::query()
                ->where('schedule_plan_id', $row->id)
                ->get()
                ->map(fn (ScheduleSession $session) => [
                    'room_id' => (string) $session->room_id,
                    'section_instructor_id' => (string) $session->section_instructor_id,
                    'course_offering_id' => (string) $session->course_offering_id,
                    'day' => (string) $session->day_value,
                    'start' => substr((string) $session->start_time, 0, 5),
                    'end' => substr((string) $session->end_time, 0, 5),
                ])
                ->values()
                ->all();

            $prePublishValidation = $generator->validateSessionsBeforePublish(
                (string) $row->semester_id,
                $sessions,
                null,
                null,
                null,
                true
            );
            if (! $prePublishValidation['ok']) {
                return response()->json([
                    'success' => false,
                    'reason' => 'final hard-constraint validation failed before publish',
                    'meta' => [
                        'hard_violations' => $prePublishValidation['hard_count'],
                        'hard_breakdown' => $prePublishValidation['hard_breakdown'],
                        'validation_errors' => $prePublishValidation['errors'],
                    ],
                ], 422);
            }
        }

        try {
            DB::transaction(function () use ($row, $data, $publishingNow, $sessionStudentAssignment): void {
                $row->update($data);
                if (! $publishingNow) {
                    return;
                }

                $sessionStudentAssignment->syncForPlan((string) $row->id);
                $postErrors = $this->postAssignmentValidationErrors(
                    (string) $row->id,
                    $sessionStudentAssignment,
                    true
                );
                if ($postErrors !== []) {
                    throw ValidationException::withMessages($postErrors);
                }
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'reason' => 'final validation failed after student assignment',
                'meta' => [
                    'validation_errors' => $this->flattenValidationErrors($e),
                ],
            ], 422);
        }

        if ($publishingNow) {
            $this->notifyPlanStakeholders(
                (string) $row->id,
                'Schedule updated',
                'A schedule plan related to your classes was updated.',
                'schedule_updated'
            );
        }
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

    public function publishFromGeneration(
        Request $request,
        ScheduleGenerateService $generator,
        ScheduleSessionStudentAssignmentService $sessionStudentAssignment,
        NotificationDispatchService $notificationDispatch
    ): JsonResponse
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
            'instructor_availabilities.*.status' => 'nullable|string|max:20',
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
        $finalValidation = $generator->validateSessionsBeforePublish(
            $request->string('semester_id')->toString(),
            $result['sessions'],
            $request->input('schedule_settings'),
            $request->string('settings_id')->toString() ?: null,
            $request->input('instructor_availabilities'),
        );
        if (! $finalValidation['ok']) {
            return response()->json([
                'success' => false,
                'reason' => 'final hard-constraint validation failed before publish',
                'sessions' => [],
                'meta' => [
                    'hard_violations' => $finalValidation['hard_count'],
                    'hard_breakdown' => $finalValidation['hard_breakdown'],
                    'validation_errors' => $finalValidation['errors'],
                ],
            ], 422);
        }

        try {
            $plan = DB::transaction(function () use ($request, $result, $sessionStudentAssignment): SemesterSchedulePlan {
                $plan = SemesterSchedulePlan::query()->create([
                    'semester_id' => $request->string('semester_id')->toString(),
                    'status' => 'published',
                    'selected_algorithm' => $request->string('algorithm')->toString(),
                    'generated_at' => now(),
                    'notes' => $request->input('notes'),
                ]);

                foreach ($result['sessions'] as $session) {
                    ScheduleSession::query()->create([
                        'schedule_plan_id' => $plan->id,
                        'room_id' => $session['room_id'],
                        'section_instructor_id' => $session['section_instructor_id'],
                        'course_offering_id' => $session['course_offering_id'],
                        'day_value' => $session['day'],
                        'start_time' => $session['start'],
                        'end_time' => $session['end'],
                    ]);
                }

                $sessionStudentAssignment->syncForPlan((string) $plan->id);
                $postErrors = $this->postAssignmentValidationErrors(
                    (string) $plan->id,
                    $sessionStudentAssignment,
                    true
                );
                if ($postErrors !== []) {
                    throw ValidationException::withMessages($postErrors);
                }

                return $plan;
            });
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'reason' => 'final validation failed after student assignment',
                'sessions' => [],
                'meta' => [
                    'validation_errors' => $this->flattenValidationErrors($e),
                ],
            ], 422);
        }

        $this->notifyPlanStakeholders(
            (string) $plan->id,
            'New schedule published',
            'A new published schedule is available for your classes.',
            'schedule_published',
            $notificationDispatch
        );

        AuditLogger::log($request->user(), 'schedules.publish', SemesterSchedulePlan::class, $plan->id, ['items' => count($result['sessions'])], $request);

        return response()->json([
            'schedule_plan' => $plan->load('sessions'),
            'meta' => array_merge($result['meta'] ?? [], [
                'final_validation' => [
                    'hard_violations' => $finalValidation['hard_count'],
                    'hard_breakdown' => $finalValidation['hard_breakdown'],
                ],
            ]),
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
     * @return array<string, list<string>>
     */
    private function postAssignmentValidationErrors(
        string $planId,
        ScheduleSessionStudentAssignmentService $sessionStudentAssignment,
        bool $enforceMinInstructorLoad
    ): array {
        $errorsByKey = [];

        $studentOverlapErrors = $sessionStudentAssignment->detectStudentOverlapErrorsForPlan($planId);
        if ($studentOverlapErrors !== []) {
            $errorsByKey['student_schedule'] = $studentOverlapErrors;
        }

        $roomCapacityErrors = $sessionStudentAssignment->detectRoomCapacityErrorsForPlan($planId);
        if ($roomCapacityErrors !== []) {
            $errorsByKey['room_capacity'] = $roomCapacityErrors;
        }

        $studentEligibilityErrors = $sessionStudentAssignment->detectStudentEligibilityErrorsForPlan($planId);
        if ($studentEligibilityErrors !== []) {
            $errorsByKey['student_eligibility'] = $studentEligibilityErrors;
        }

        $instructorLoadErrors = $sessionStudentAssignment->detectInstructorWeeklyLoadErrorsForPlan($planId, $enforceMinInstructorLoad);
        if ($instructorLoadErrors !== []) {
            $errorsByKey['instructor_workload'] = $instructorLoadErrors;
        }

        return $errorsByKey;
    }

    /**
     * @return list<string>
     */
    private function flattenValidationErrors(ValidationException $e): array
    {
        $out = [];
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $out[] = "{$key}: {$message}";
            }
        }

        return $out === [] ? ['Validation failed.'] : $out;
    }

    private function notifyPlanStakeholders(
        string $planId,
        string $title,
        string $message,
        string $type,
        ?NotificationDispatchService $notificationDispatch = null
    ): void {
        $dispatch = $notificationDispatch ?? app(NotificationDispatchService::class);

        $instructorUserIds = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->join('course_section_instructors', 'course_section_instructors.id', '=', 'schedule_sessions.section_instructor_id')
            ->join('instructors', 'instructors.id', '=', 'course_section_instructors.instructor_id')
            ->pluck('instructors.user_id')
            ->unique()
            ->values()
            ->all();

        $studentUserIds = ScheduleSessionStudent::query()
            ->join('schedule_sessions', 'schedule_sessions.id', '=', 'schedule_session_students.schedule_session_id')
            ->where('schedule_sessions.schedule_plan_id', $planId)
            ->pluck('schedule_session_students.student_id')
            ->unique()
            ->values()
            ->all();

        $dispatch->notifyUsers(
            $instructorUserIds,
            $title,
            $message,
            $type,
            'high',
            [
                'route' => '/instructor/weekly-schedule',
                'link' => '/instructor/weekly-schedule',
                'plan_id' => $planId,
            ]
        );

        $dispatch->notifyUsers(
            $studentUserIds,
            $title,
            $message,
            $type,
            'high',
            [
                'route' => '/student/weekly-schedule',
                'link' => '/student/weekly-schedule',
                'plan_id' => $planId,
            ]
        );
    }
}
