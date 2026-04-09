<?php

namespace App\Http\Controllers\Api\Student;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StudentPdfController extends Controller
{
    public function weeklySchedule(Request $request, StudentScheduleController $sched): Response
    {
        $response = $sched->weekly($request);
        $json = json_decode($response->getContent(), true) ?? [];
        $pdf = Pdf::loadView('pdf.student_weekly', [
            'user' => $request->user(),
            'data' => $json,
        ]);

        return $pdf->download('student-weekly-schedule.pdf');
    }

    public function grades(Request $request, StudentGradeController $grades): Response
    {
        $response = $grades->bySemester($request);
        $json = json_decode($response->getContent(), true) ?? [];
        $pdf = Pdf::loadView('pdf.student_grades', [
            'user' => $request->user(),
            'grades' => $json,
        ]);

        return $pdf->download('grades.pdf');
    }
}
