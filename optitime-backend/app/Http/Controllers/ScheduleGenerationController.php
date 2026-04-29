<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateScheduleRequest;
use App\Models\ScheduleGenerationJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleGenerationController extends Controller
{
    public function generate(GenerateScheduleRequest $request): JsonResponse
    {
        $v = $request->validated();
        $job = ScheduleGenerationJob::query()->create([
            'requested_by_user_id' => $request->user()?->id,
            'status' => 'queued',
            'algorithm' => (string) $v['algorithm'],
            'semester_id' => (string) $v['semester_id'],
            'request_payload' => $v,
            'progress' => 0,
            'queued_at' => now(),
        ]);

        return response()->json([
            'job_id' => $job->id,
            'status' => $job->status,
            'message' => 'Schedule generation queued. It will be processed by cron.',
        ], 202);
    }

    public function show(string $id, Request $request): JsonResponse
    {
        $job = ScheduleGenerationJob::query()->findOrFail($id);
        if ($job->requested_by_user_id !== null && $request->user() !== null && $job->requested_by_user_id !== $request->user()->id) {
            abort(403);
        }

        $response = [
            'job_id' => $job->id,
            'status' => $job->status,
            'progress' => (int) $job->progress,
            'algorithm' => $job->algorithm,
            'semester_id' => $job->semester_id,
            'queued_at' => $job->queued_at,
            'started_at' => $job->started_at,
            'finished_at' => $job->finished_at,
            'error_message' => $job->error_message,
        ];

        if ($job->status === 'completed') {
            $response['result'] = $job->result_payload;
        }

        return response()->json($response);
    }
}
