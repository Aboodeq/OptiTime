<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Services\AuditLogger;
use App\Services\SectionInstructorSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSectionInstructorController extends Controller
{
    public function __construct(
        private SectionInstructorSyncService $sectionInstructorSyncService
    ) {}

    public function sync(Request $request, string $section): JsonResponse
    {
        $data = $request->validate([
            'instructor_ids' => 'required|array',
            'instructor_ids.*' => 'uuid|exists:instructors,id',
        ]);

        $sectionModel = $this->sectionInstructorSyncService->sync($section, $data['instructor_ids']);

        AuditLogger::log($request->user(), 'sections.instructors.sync', CourseSection::class, $section, $data, $request);

        return response()->json($sectionModel);
    }
}
