<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Api\Instructor\InstructorScheduleController;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstructorPdfController extends Controller
{
    public function weeklySchedule(Request $request, InstructorScheduleController $sched): Response
    {
        $response = $sched->weekly($request);
        $json = json_decode($response->getContent(), true) ?? [];
        $pdf = Pdf::loadView('pdf.instructor_weekly', [
            'user' => $request->user(),
            'data' => $json,
        ]);

        return $pdf->download('weekly-schedule.pdf');
    }
}
