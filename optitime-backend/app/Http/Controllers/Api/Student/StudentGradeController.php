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
            $course = $g->session->courseOffering->course;

            return [
                'oral' => $g->oral,
                'lab' => $g->lab,
                'midterm' => $g->midterm,
                'final' => $g->final,
                'numeric_grade' => $g->total,
                'letter_grade' => $g->letter_grade,
                'section' => [
                    'section_name' => $g->session->sectionInstructor->section->section_name,
                    'course' => [
                        'code' => $course->code,
                        'name' => $course->name,
                    ],
                ],
            ];
        })->values()->all();

        return response()->json($grades);
    }
}
