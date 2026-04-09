<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use App\Scheduling\DayMapping;
use App\Services\ScheduleSettingAdminSerializer;
use App\Services\WeeklyScheduleSemesterResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentScheduleController extends Controller
{
    public function weekly(Request $request): JsonResponse
    {
        $stu = $request->user()->student;
        abort_if(! $stu, 403);

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
            ->whereHas(
                'sessionStudents',
                fn ($sub) => $sub->where('student_id', $stu->user_id)
            )
            ->with([
                'room',
                'courseOffering.course',
                'sectionInstructor.section',
                'sectionInstructor.instructor.user',
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
                    'course' => [
                        'id' => $s->courseOffering->course->id,
                        'code' => $s->courseOffering->course->code,
                        'name' => $s->courseOffering->course->name,
                    ],
                ],
                'room' => [
                    'name' => $s->room->name,
                ],
            ];
        })->values()->all();

        return response()->json(array_merge($base, [
            'schedule' => $plan,
            'items' => $items,
        ]));
    }
}
