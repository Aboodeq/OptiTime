<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\LectureRequest;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CoordinatorLectureRequestController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            LectureRequest::query()
                ->with([
                    'instructor.user',
                    'scheduleSession.courseOffering.course',
                    'scheduleSession.sectionInstructor.section',
                ])
                ->orderByDesc('created_at')
                ->get()
        );
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $row = LectureRequest::query()->findOrFail($id);
        $data = $request->validate([
            'status' => 'required|string|max:20',
            'review_note' => 'nullable|string',
        ]);
        $data['reviewed_at'] = now();
        $row->update($data);
        AuditLogger::log($request->user(), 'lecture_requests.update', LectureRequest::class, $id, $data, $request);

        return response()->json($row->fresh()->load([
            'instructor.user',
            'scheduleSession.courseOffering.course',
            'scheduleSession.sectionInstructor.section',
        ]));
    }
}
