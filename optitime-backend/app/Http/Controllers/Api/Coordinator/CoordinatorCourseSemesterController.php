<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorCourseSemesterController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $data = $request->validate([
            'course_id' => 'required|uuid|exists:courses,id',
            'semester_id' => 'required|uuid|exists:semesters,id',
            'status' => 'required|string|max:32',
        ]);
        $active = in_array(strtolower($data['status']), ['active', 'published', '1', 'true'], true);
        CourseOffering::query()->updateOrCreate(
            [
                'course_id' => $data['course_id'],
                'semester_id' => $data['semester_id'],
            ],
            ['is_active' => $active]
        );
        AuditLogger::log($request->user(), 'course_offering.sync', 'course_offerings', $data['course_id'], $data, $request);

        return response()->json(['ok' => true]);
    }
}
