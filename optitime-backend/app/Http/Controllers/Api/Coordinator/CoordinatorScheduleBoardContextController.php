<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use App\Services\ScheduleSettingAdminSerializer;
use Illuminate\Http\JsonResponse;

class CoordinatorScheduleBoardContextController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(ScheduleSettingAdminSerializer::toWeeklyBoardContext());
    }
}
