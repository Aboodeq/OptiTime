<?php

namespace App\Console\Commands;

use App\Models\ScheduleGenerationJob;
use App\Services\ScheduleGenerateService;
use Illuminate\Console\Command;

class ProcessScheduleGenerationJobs extends Command
{
    protected $signature = 'schedule-generation:process {--limit=1 : Number of queued jobs to process}';

    protected $description = 'Process queued schedule generation jobs without a daemon worker.';

    public function handle(ScheduleGenerateService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $processed = 0;

        while ($processed < $limit) {
            $job = ScheduleGenerationJob::query()
                ->where('status', 'queued')
                ->orderBy('queued_at')
                ->orderBy('created_at')
                ->first();

            if (! $job) {
                break;
            }

            $claimed = ScheduleGenerationJob::query()
                ->where('id', $job->id)
                ->where('status', 'queued')
                ->update([
                    'status' => 'running',
                    'progress' => 10,
                    'started_at' => now(),
                ]);

            if ($claimed < 1) {
                continue;
            }

            $payload = (array) ($job->request_payload ?? []);
            try {
                $result = $service->generate(
                    (string) ($payload['algorithm'] ?? 'genetic'),
                    (string) ($payload['semester_id'] ?? ''),
                    $payload['schedule_settings'] ?? null,
                    $payload['settings_id'] ?? null,
                    $payload['baseDraft'] ?? null,
                    $payload['rooms_override'] ?? null,
                    isset($payload['seed']) ? (int) $payload['seed'] : null,
                    $payload['instructor_availabilities'] ?? null,
                );

                $job->update([
                    'status' => ($result['success'] ?? false) ? 'completed' : 'failed',
                    'progress' => 100,
                    'result_payload' => $result,
                    'error_message' => ($result['success'] ?? false) ? null : ($result['reason'] ?? 'Generation failed'),
                    'finished_at' => now(),
                ]);
            } catch (\Throwable $e) {
                $job->update([
                    'status' => 'failed',
                    'progress' => 100,
                    'error_message' => $e->getMessage(),
                    'finished_at' => now(),
                ]);
            }

            $processed++;
        }

        $this->info("Processed {$processed} generation job(s).");

        return self::SUCCESS;
    }
}
