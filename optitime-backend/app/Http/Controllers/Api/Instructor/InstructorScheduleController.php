<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use App\Scheduling\DayMapping;
use App\Services\ScheduleSettingAdminSerializer;
use App\Services\WeeklyScheduleSemesterResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorScheduleController extends Controller
{
    public function weekly(Request $request): JsonResponse
    {
        $ins = $request->user()->instructor;
        abort_if(! $ins, 403);

        $semesterId = WeeklyScheduleSemesterResolver::resolveSemesterId($request);
        $semesterPayload = WeeklyScheduleSemesterResolver::semesterPayload($semesterId);

        $board = ScheduleSettingAdminSerializer::toWeeklyBoardContext();
        unset($board['settings_id']);

        $base = [
            'semester' => $semesterPayload,
            'board' => $board,
        ];

        if ($semesterId === null) {
            return response()->json(array_merge($base, [
                'schedule' => null,
                'items' => [],
            ]));
        }

        $plan = SemesterSchedulePlan::query()
            ->where('semester_id', $semesterId)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->first();
        if (! $plan) {
            return response()->json(array_merge($base, [
                'schedule' => null,
                'items' => [],
            ]));
        }

        $sessions = ScheduleSession::query()
            ->where('schedule_plan_id', $plan->id)
            ->whereHas('sectionInstructor', fn ($q) => $q->where('instructor_id', $ins->id))
            ->with([
                'room',
                'courseOffering.course',
                'sectionInstructor.section',
                'sectionInstructor.instructor.user',
                'sessionStudents.student.user',
            ])
            ->orderBy('day_value')
            ->orderBy('start_time')
            ->get();

        $items = $sessions->map(function ($s) {
            $insUser = $s->sectionInstructor->instructor->user ?? null;

            return [
                'id' => $s->id,
                'day_of_week' => DayMapping::toLong($s->day_value),
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'course_id' => $s->courseOffering->course_id,
                'section_id' => $s->sectionInstructor->section_id,
                'instructor_id' => $s->sectionInstructor->instructor_id,
                'instructor_name' => $insUser?->full_name ?? '',
                'course_offering_id' => $s->course_offering_id,
                'section_instructor_id' => $s->section_instructor_id,
                'room_id' => $s->room_id,
                'section' => [
                    'id' => $s->sectionInstructor->section->id,
                    'section_name' => $s->sectionInstructor->section->section_name,
                    'section_number' => $s->sectionInstructor->section->section_name,
                    'course' => [
                        'id' => $s->courseOffering->course->id,
                        'code' => $s->courseOffering->course->code,
                        'name' => $s->courseOffering->course->name,
                    ],
                ],
                'room' => [
                    'name' => $s->room->name,
                ],
                'students' => $s->sessionStudents->map(function ($ss) {
                    $st = $ss->student;
                    $u = $st?->user;

                    return [
                        'id' => $st?->user_id,
                        'name' => $u?->full_name ?? '',
                        'university_number' => $st?->university_number ?? '',
                        'year_level' => $st?->year_level,
                        'study_status' => $st?->study_status,
                    ];
                })->values()->all(),
            ];
        })->values()->all();

        return response()->json(array_merge($base, [
            'schedule' => $plan,
            'items' => $items,
        ]));
    }
}
