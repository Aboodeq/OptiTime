<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use App\Scheduling\DayMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentScheduleController extends Controller
{
    public function weekly(Request $request): JsonResponse
    {
        $stu = $request->user()->student;
        abort_if(! $stu, 403);
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);

        $plan = SemesterSchedulePlan::query()
            ->where('semester_id', $request->semester_id)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->first();
        if (! $plan) {
            return response()->json(['schedule' => null, 'items' => []]);
        }

        $sessions = ScheduleSession::query()
            ->where('schedule_plan_id', $plan->id)
            ->whereHas('sessionStudents', fn ($q) => $q->where('student_id', $stu->user_id))
            ->with(['room', 'courseOffering.course', 'sectionInstructor.section'])
            ->orderBy('day_value')
            ->orderBy('start_time')
            ->get();

        $items = $sessions->map(function ($s) {
            return [
                'id' => $s->id,
                'day_of_week' => DayMapping::toLong($s->day_value),
                'start_time' => substr((string) $s->start_time, 0, 5),
                'end_time' => substr((string) $s->end_time, 0, 5),
                'section' => [
                    'section_name' => $s->sectionInstructor->section->section_name,
                    'course' => [
                        'code' => $s->courseOffering->course->code,
                        'name' => $s->courseOffering->course->name,
                    ],
                ],
                'room' => [
                    'name' => $s->room->name,
                ],
            ];
        })->values()->all();

        return response()->json(['schedule' => $plan, 'items' => $items]);
    }
}
