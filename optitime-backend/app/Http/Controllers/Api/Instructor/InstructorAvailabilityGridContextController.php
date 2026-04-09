<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\ScheduleSetting;
use App\Services\ScheduleSettingAdminSerializer;
use Illuminate\Http\JsonResponse;

class InstructorAvailabilityGridContextController extends Controller
{
    public function show(): JsonResponse
    {
        $row = ScheduleSetting::query()
            ->orderBy('created_at')
            ->firstOrFail();

        return response()->json(ScheduleSettingAdminSerializer::toInstructorAvailabilityGridContext($row));
    }
}
