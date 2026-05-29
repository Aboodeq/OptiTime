<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSessionStudent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentGradeController extends Controller
{
    public function bySemester(Request $request): JsonResponse
    {
        $stu = $request->user()->student;
        abort_if(! $stu, 403);
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);

        $rows = ScheduleSessionStudent::query()
            ->where('student_id', $stu->user_id)
            ->whereHas('session.plan', fn ($q) => $q->where('semester_id', $request->semester_id))
            ->with(['session.courseOffering.course', 'session.sectionInstructor.section'])
            ->get();

        $grades = $rows->map(function (ScheduleSessionStudent $g) {
            $session = $g->session;
            $course = $session->courseOffering->course;

            return [
                'id' => (string) $g->id,
                'schedule_session_id' => (string) $session->id,
                'student_id' => (string) $g->student_id,
                'oral' => $g->oral,
                'lab' => $g->lab,
                'midterm' => $g->midterm,
                'final' => $g->final,
                'total' => $g->total,
                'numeric_grade' => $g->total,
                'letter_grade' => $g->letter_grade,
                'day' => (string) $session->day_value,
                'start' => substr((string) $session->start_time, 0, 5),
                'end' => substr((string) $session->end_time, 0, 5),
                'section' => [
                    'section_name' => $session->sectionInstructor->section->section_name,
                    'course' => [
                        'code' => $course->code,
                        'name' => $course->name_en ?? $course->name,
                    ],
                ],
            ];
        })->values()->all();

        return response()->json($grades);
    }
}
