<?php

namespace App\Http\Controllers\Api\Management;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\SemesterSchedulePlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementReportController extends Controller
{
    private function publishedPlanId(string $semesterId): ?string
    {
        return SemesterSchedulePlan::query()
            ->where('semester_id', $semesterId)
            ->where('status', 'published')
            ->orderByDesc('created_at')
            ->value('id');
    }

    public function classroomOccupancy(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $planId = $this->publishedPlanId($request->semester_id);
        if (! $planId) {
            return response()->json(['used_rooms' => 0, 'total_rooms' => Room::query()->count(), 'rate' => 0]);
        }
        $used = (int) ScheduleSession::query()->where('schedule_plan_id', $planId)->selectRaw('COUNT(DISTINCT room_id) as c')->value('c');
        $total = max(1, Room::query()->count());

        return response()->json([
            'used_rooms' => $used,
            'total_rooms' => $total,
            'occupancy_rate' => round($used / $total, 4),
        ]);
    }

    public function labUtilization(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $planId = $this->publishedPlanId($request->semester_id);
        $labRooms = Room::query()->where('type', 'lab')->pluck('id');
        if (! $planId || $labRooms->isEmpty()) {
            return response()->json(['lab_sessions' => 0, 'lab_rooms' => $labRooms->count()]);
        }
        $count = ScheduleSession::query()->where('schedule_plan_id', $planId)->whereIn('room_id', $labRooms)->count();

        return response()->json([
            'lab_sessions' => $count,
            'lab_rooms' => $labRooms->count(),
        ]);
    }

    public function studyHoursDistribution(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $planId = $this->publishedPlanId($request->semester_id);
        if (! $planId) {
            return response()->json([]);
        }
        $rows = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->selectRaw('day_value as day_of_week, COUNT(*) as sessions')
            ->groupBy('day_value')
            ->get();

        return response()->json($rows);
    }

    public function peakPeriods(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $planId = $this->publishedPlanId($request->semester_id);
        if (! $planId) {
            return response()->json([]);
        }
        $rows = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->selectRaw('start_time, COUNT(*) as concurrent_sections')
            ->groupBy('start_time')
            ->orderByDesc('concurrent_sections')
            ->limit(10)
            ->get();

        return response()->json($rows);
    }

    public function resourceUtilization(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'nullable|uuid|exists:semesters,id']);

        return response()->json(DB::table('resources')->orderBy('name_en')->get());
    }

    public function complianceRestrictions(Request $request): JsonResponse
    {
        $request->validate(['semester_id' => 'required|uuid|exists:semesters,id']);
        $planId = $this->publishedPlanId($request->semester_id);
        if (! $planId) {
            return response()->json(['violations' => [], 'ok' => true]);
        }
        $items = ScheduleSession::query()
            ->where('schedule_plan_id', $planId)
            ->with(['sectionInstructor.instructor', 'room'])
            ->get();
        $violations = [];
        $pairs = $items->values();
        for ($i = 0; $i < $pairs->count(); $i++) {
            for ($j = $i + 1; $j < $pairs->count(); $j++) {
                $a = $pairs[$i];
                $b = $pairs[$j];
                if ($a->day_value !== $b->day_value) {
                    continue;
                }
                if ($a->room_id === $b->room_id && $this->timeOverlap($a->start_time, $a->end_time, $b->start_time, $b->end_time)) {
                    $violations[] = ['type' => 'room_overlap', 'items' => [$a->id, $b->id]];
                }
                $insA = $a->sectionInstructor->instructor_id;
                $insB = $b->sectionInstructor->instructor_id;
                if ($insA === $insB && $this->timeOverlap($a->start_time, $a->end_time, $b->start_time, $b->end_time)) {
                    $violations[] = ['type' => 'instructor_overlap', 'items' => [$a->id, $b->id]];
                }
            }
        }

        return response()->json(['ok' => $violations === [], 'violations' => $violations]);
    }

    private function timeOverlap(string $aStart, string $aEnd, string $bStart, string $bEnd): bool
    {
        $aS = substr($aStart, 0, 8);
        $aE = substr($aEnd, 0, 8);
        $bS = substr($bStart, 0, 8);
        $bE = substr($bEnd, 0, 8);

        return strcmp($aS, $bE) < 0 && strcmp($bS, $aE) < 0;
    }
}
