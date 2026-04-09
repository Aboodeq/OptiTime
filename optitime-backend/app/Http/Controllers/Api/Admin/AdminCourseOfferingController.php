<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseOffering;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCourseOfferingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'semester_id' => 'required|uuid|exists:semesters,id',
        ]);

        $rows = CourseOffering::query()
            ->where('semester_id', $data['semester_id'])
            ->orderBy('created_at')
            ->get();

        return response()->json($rows);
    }
}
