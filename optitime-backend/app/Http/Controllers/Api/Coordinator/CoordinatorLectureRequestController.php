<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\LectureRequest;
use App\Models\ScheduleSessionStudent;
use App\Scheduling\DayMapping;
use App\Services\AuditLogger;
use App\Services\NotificationDispatchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorLectureRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            LectureRequest::query()
                ->with([
                    'instructor.user',
                    'scheduleSession.courseOffering.course',
                    'scheduleSession.sectionInstructor.section',
                ])
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function update(Request $request, string $id, NotificationDispatchService $notificationDispatch): JsonResponse
    {
        $row = LectureRequest::query()->with('instructor')->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|string|max:20',
            'review_note' => 'nullable|string',
        ]);
        $data['reviewed_at'] = now();
        $row->update($data);

        $row->load([
            'scheduleSession.room',
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
            'scheduleSession.sectionInstructor.instructor.user',
            'instructor.user',
        ]);

        $studentIds = ScheduleSessionStudent::query()
            ->where('schedule_session_id', (string) $row->schedule_session_id)
            ->pluck('student_id')
            ->unique()
            ->values()
            ->all();

        $status = (string) $row->status;
        $notificationDispatch->notifyUsers(
            [(string) $row->instructor->user_id],
            'Lecture request reviewed',
            "Your lecture request status is now: {$status}.",
            'lecture_request_reviewed',
            'high',
            [
                'route' => '/instructor/lecture-requests',
                'link' => '/instructor/lecture-requests',
                'lecture_request_id' => (string) $row->id,
                'status' => $status,
            ]
        );

        if (in_array($status, ['approved', 'accepted', 'rejected'], true)) {
            $course = $row->scheduleSession?->courseOffering?->course;
            $courseLabel = $course ? (($course->code ? $course->code.' — ' : '').$course->name) : 'your lecture';
            $notificationDispatch->notifyUsers(
                $studentIds,
                'Lecture schedule change request reviewed',
                "The coordinator reviewed a request for {$courseLabel}.",
                'lecture_request_reviewed',
                'medium',
                $this->studentLectureReviewNotificationPayload($row, $status)
            );
        }

        AuditLogger::log($request->user(), 'lecture_requests.update', LectureRequest::class, $id, $data, $request);

        return response()->json($row->fresh()->load([
            'instructor.user',
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function studentLectureReviewNotificationPayload(LectureRequest $row, string $status): array
    {
        $session = $row->scheduleSession;
        $insUser = $session?->sectionInstructor?->instructor?->user;

        $scheduleSessionPayload = null;
        if ($session) {
            $course = $session->courseOffering?->course;
            $section = $session->sectionInstructor?->section;

            $scheduleSessionPayload = [
                'id' => (string) $session->id,
                'day_value' => (string) $session->day_value,
                'day_of_week' => DayMapping::toLong((string) $session->day_value),
                'start_time' => substr((string) $session->start_time, 0, 5),
                'end_time' => substr((string) $session->end_time, 0, 5),
                'room' => [
                    'id' => (string) $session->room_id,
                    'name' => (string) ($session->room?->name ?? ''),
                ],
                'course' => $course ? [
                    'id' => (string) $course->id,
                    'code' => (string) $course->code,
                    'name' => (string) $course->name,
                ] : null,
                'section' => $section ? [
                    'id' => (string) $section->id,
                    'section_name' => (string) $section->section_name,
                ] : null,
                'instructor' => [
                    'id' => (string) ($session->sectionInstructor?->instructor_id ?? ''),
                    'name' => (string) ($insUser?->full_name ?? ''),
                ],
            ];
        }

        return [
            'route' => '/student/weekly-schedule',
            'link' => '/student/weekly-schedule',
            'lecture_request_id' => (string) $row->id,
            'schedule_session_id' => (string) $row->schedule_session_id,
            'status' => $status,
            'lecture_request' => [
                'id' => (string) $row->id,
                'request_type' => (string) $row->request_type,
                'requested_date' => $row->requested_date?->format('Y-m-d'),
                'note' => $row->note,
                'status' => $status,
                'review_note' => $row->review_note,
                'reviewed_at' => $row->reviewed_at?->toIso8601String(),
            ],
            'schedule_session' => $scheduleSessionPayload,
        ];
    }
}
