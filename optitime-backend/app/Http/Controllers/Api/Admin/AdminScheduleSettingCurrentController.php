<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateScheduleSettingCurrentRequest;
use App\Models\ScheduleSetting;
use App\Services\AuditLogger;
use App\Services\ScheduleSettingAdminSerializer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminScheduleSettingCurrentController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $row = ScheduleSetting::query()
            ->orderBy('created_at')
            ->with(['constraints', 'roomConstraints', 'studyDays', 'breakTimes', 'loadRanges'])
            ->firstOrFail();

        return response()->json(ScheduleSettingAdminSerializer::toAdminArray($row));
    }

    public function update(UpdateScheduleSettingCurrentRequest $request): JsonResponse
    {
        $row = ScheduleSetting::query()->orderBy('created_at')->firstOrFail();
        $data = $request->validated();

        DB::transaction(function () use ($row, $data, $request): void {
            ScheduleSettingAdminSerializer::syncFromAdminArray($row, $data);
            AuditLogger::log(
                $request->user(),
                'schedule_settings.update_current',
                ScheduleSetting::class,
                $row->id,
                ['capacity_threshold' => $data['capacity_threshold'] ?? null],
                $request,
            );
        });

        $fresh = ScheduleSetting::query()
            ->whereKey($row->id)
            ->with(['constraints', 'roomConstraints', 'studyDays', 'breakTimes', 'loadRanges'])
            ->firstOrFail();

        return response()->json(ScheduleSettingAdminSerializer::toAdminArray($fresh));
    }
}
