<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateScheduleRequest;
use App\Services\ScheduleGenerateService;
use Illuminate\Http\JsonResponse;

class ScheduleGenerationController extends Controller
{
    public function generate(GenerateScheduleRequest $request, ScheduleGenerateService $service): JsonResponse
    {
        $v = $request->validated();
        $data = $service->generate(
            $v['algorithm'],
            $v['semester_id'],
            $v['schedule_settings'] ?? null,
            $v['settings_id'] ?? null,
            $v['baseDraft'] ?? null,
            $v['rooms_override'] ?? null,
            isset($v['seed']) ? (int) $v['seed'] : null,
            $v['instructor_availabilities'] ?? null,
        );

        $status = $data['success'] ? 200 : 422;

        return response()->json($data, $status);
    }
}
