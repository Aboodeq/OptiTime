<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Models\ScheduleSession;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ExamSessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $semesterId = $request->semester_id;

        $sectionIds = CourseSectionInstructor::query()
            ->whereHas('scheduleSessions', function ($q) use ($semesterId): void {
                $q->whereHas('plan', fn ($p) => $p->where('semester_id', $semesterId)->where('status', 'published'));
            })
            ->pluck('section_id')
            ->unique()
            ->values();

        $sections = CourseSection::query()
            ->whereIn('id', $sectionIds)
            ->with('course')
            ->orderBy('id')
            ->get();

        $sections->each(function (CourseSection $section) use ($semesterId): void {
            $sessions = ScheduleSession::query()
                ->whereHas('plan', fn ($q) => $q->where('semester_id', $semesterId)->where('status', 'published'))
                ->whereHas('sectionInstructor', fn ($q) => $q->where('section_id', $section->id))
                ->get();
            $mapped = $sessions->map(fn (ScheduleSession $s) => (object) [
                'id' => $s->id,
                'schedule_session_id' => $s->id,
                'section_id' => $section->id,
                'semester_id' => $semesterId,
                'is_done' => $s->grades_marked_done_at !== null,
                'completed_at' => $s->grades_marked_done_at,
            ]);
            $section->setRelation('examSessions', new Collection($mapped->all()));
        });

        return response()->json($sections);
    }

    public function markDone(Request $request): JsonResponse
    {
        $data = $request->validate([
            'section_id' => 'required|uuid|exists:course_sections,id',
            'semester_id' => 'required|uuid|exists:semesters,id',
        ]);
        $updated = ScheduleSession::query()
            ->whereHas('plan', fn ($q) => $q->where('semester_id', $data['semester_id'])->where('status', 'published'))
            ->whereHas('sectionInstructor', fn ($q) => $q->where('section_id', $data['section_id']))
            ->update([
                'grades_marked_done_at' => now(),
                'grades_entry_status' => 'closed',
            ]);
        AuditLogger::log($request->user(), 'exam_sessions.done', ScheduleSession::class, (string) $data['section_id'], array_merge($data, ['sessions_updated' => $updated]), $request);

        return response()->json([
            'section_id' => $data['section_id'],
            'semester_id' => $data['semester_id'],
            'sessions_updated' => $updated,
        ]);
    }
}
