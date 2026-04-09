<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorSectionInstructorController extends Controller
{
    public function sync(Request $request, string $section): JsonResponse
    {
        CourseSection::query()->findOrFail($section);
        $data = $request->validate([
            'instructor_ids' => 'required|array',
            'instructor_ids.*' => 'uuid|exists:instructors,id',
        ]);
        CourseSectionInstructor::query()->where('section_id', $section)->delete();
        foreach ($data['instructor_ids'] as $iid) {
            CourseSectionInstructor::query()->create([
                'section_id' => $section,
                'instructor_id' => $iid,
            ]);
        }
        AuditLogger::log($request->user(), 'sections.instructors.sync', CourseSection::class, $section, $data, $request);

        return response()->json(
            CourseSection::query()->with('instructors')->findOrFail($section)
        );
    }
}
