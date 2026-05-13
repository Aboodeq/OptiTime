<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\Room;
use App\Models\ScheduleGenerationJob;
use App\Models\ScheduleSettingConstraint;
use App\Models\ScheduleSetting;
use App\Models\Semester;
use App\Models\User;
use App\Services\ScheduleGenerateService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function actingAsCoordinator(): User
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        assert($user instanceof User);
        Sanctum::actingAs($user);

        return $user;
    }

    private function actingAsStudent(): User
    {
        $user = User::query()->where('email', 'student1@optitime.local')->firstOrFail();
        assert($user instanceof User);
        Sanctum::actingAs($user);

        return $user;
    }

    private function queuePayload(string $algorithm = 'backtracking'): array
    {
        $semesterId = (string) Semester::query()->value('id');

        $csiOne = CourseSectionInstructor::query()
            ->whereHas('section', fn ($q) => $q->where('section_type', 'room'))
            ->with('section')
            ->firstOrFail();

        $csiTwo = CourseSectionInstructor::query()
            ->where('id', '!=', $csiOne->id)
            ->whereHas('section', fn ($q) => $q->where('section_type', 'room'))
            ->with('section')
            ->firstOrFail();

        $offeringOne = CourseOffering::query()
            ->where('course_id', $csiOne->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();

        $offeringTwo = CourseOffering::query()
            ->where('course_id', $csiTwo->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();

        $roomId = (string) Room::query()->where('type', '!=', 'lab')->orderByDesc('capacity')->value('id');

        return [
            'algorithm' => $algorithm,
            'semester_id' => $semesterId,
            'seed' => 42,
            'baseDraft' => [
                'sessions' => [
                    [
                        'course_offering_id' => $offeringOne->id,
                        'section_instructor_id' => $csiOne->id,
                        'day' => 'sun',
                        'start' => '08:00',
                        'end' => '08:50',
                        'room_id' => $roomId,
                    ],
                    [
                        'course_offering_id' => $offeringTwo->id,
                        'section_instructor_id' => $csiTwo->id,
                    ],
                ],
            ],
        ];
    }

    private function queueAndProcess(array $payload): ScheduleGenerationJob
    {
        $enqueue = $this->postJson('/api/schedule/generate', $payload);
        $enqueue->assertStatus(202);
        $jobId = (string) $enqueue->json('job_id');

        $this->artisan('schedule-generation:process --limit=1')->assertExitCode(0);

        return ScheduleGenerationJob::query()->findOrFail($jobId);
    }

    public function test_generate_requires_authentication(): void
    {
        $semesterId = Semester::query()->value('id');
        $response = $this->postJson('/api/schedule/generate', [
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
        ]);

        $response->assertStatus(401);
    }

    public function test_generate_queues_job_for_coordinator(): void
    {
        $this->actingAsCoordinator();

        $response = $this->postJson('/api/schedule/generate', $this->queuePayload('backtracking'));

        $response->assertStatus(202);
        $response->assertJsonPath('status', 'queued');
        $response->assertJsonStructure(['job_id', 'status', 'message']);
    }

    public function test_generate_forbidden_for_student_in_non_local_env(): void
    {
        $this->app['env'] = 'staging';
        $this->actingAsStudent();

        $response = $this->postJson('/api/schedule/generate', $this->queuePayload('backtracking'));

        $response->assertStatus(403);
    }

    public function test_backtracking_job_processes_and_preserves_fixed_session(): void
    {
        $this->actingAsCoordinator();
        $payload = $this->queuePayload('backtracking');
        $job = $this->queueAndProcess($payload);

        $this->assertSame('completed', $job->status);
        $result = (array) $job->result_payload;
        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertSame('backtracking', $result['algorithm'] ?? null);

        $sessions = (array) ($result['sessions'] ?? []);
        $this->assertNotEmpty($sessions);

        $fixedSid = $payload['baseDraft']['sessions'][0]['section_instructor_id'];
        $fixed = collect($sessions)->firstWhere('section_instructor_id', $fixedSid);
        $this->assertNotNull($fixed);
        $this->assertSame('sun', $fixed['day']);
        $this->assertSame('08:00', $fixed['start']);
    }

    public function test_genetic_job_processes_and_returns_meta_diagnostics(): void
    {
        $this->actingAsCoordinator();
        $job = $this->queueAndProcess($this->queuePayload('genetic'));

        $this->assertSame('completed', $job->status);
        $result = (array) $job->result_payload;
        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertSame('genetic', $result['algorithm'] ?? null);
        $this->assertIsArray($result['meta'] ?? null);
        $this->assertArrayHasKey('generations', $result['meta']);
        $this->assertArrayHasKey('hard_breakdown', $result['meta']);
        $this->assertArrayHasKey('soft_penalty', $result['meta']);
        $this->assertArrayHasKey('ga_termination_reason', $result['meta']);
        $this->assertArrayHasKey('ga_effective_population', $result['meta']);
        $this->assertArrayHasKey('ga_effective_generations', $result['meta']);
        $this->assertArrayHasKey('ga_stagnation_generations', $result['meta']);
        $this->assertArrayHasKey('ga_low_gain_stagnation_generations', $result['meta']);
    }

    public function test_processed_job_can_be_polled_through_show_endpoint(): void
    {
        $this->actingAsCoordinator();
        $job = $this->queueAndProcess($this->queuePayload('backtracking'));

        $response = $this->getJson('/api/schedule/generate/'.$job->id);

        $response->assertOk();
        $response->assertJsonPath('job_id', $job->id);
        $response->assertJsonPath('status', 'completed');
        $response->assertJsonPath('result.success', true);
    }

    public function test_failed_backtracking_job_includes_hard_breakdown_diagnostics(): void
    {
        $this->actingAsCoordinator();
        $payload = $this->queuePayload('backtracking');

        $first = $payload['baseDraft']['sessions'][0];
        $payload['baseDraft']['sessions'][1]['day'] = $first['day'];
        $payload['baseDraft']['sessions'][1]['start'] = $first['start'];
        $payload['baseDraft']['sessions'][1]['end'] = $first['end'];
        $payload['baseDraft']['sessions'][1]['room_id'] = $first['room_id'];

        $job = $this->queueAndProcess($payload);

        $this->assertSame('failed', $job->status);
        $result = (array) $job->result_payload;
        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('hard_breakdown', $result['meta']);
        $this->assertArrayHasKey('no_room_overlap', $result['meta']['hard_breakdown']);
        $this->assertArrayHasKey('validation_errors', $result['meta']);
        $this->assertArrayHasKey('diagnostics', $result['meta']);
        $this->assertArrayHasKey('termination_reason', $result['meta']);
    }

    public function test_backtracking_low_time_limit_fails_fast_with_termination_diagnostics(): void
    {
        $this->actingAsCoordinator();

        config([
            'optitime.backtracking.max_seconds' => 0.0001,
            'optitime.backtracking.max_backtracks' => 1_000_000,
            'optitime.backtracking.max_recursive_steps' => 1_000_000,
        ]);

        $job = $this->queueAndProcess($this->queuePayload('backtracking'));

        $this->assertSame('failed', $job->status);
        $result = (array) $job->result_payload;
        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertSame('max_seconds_exceeded', data_get($result, 'meta.termination_reason'));
        $this->assertIsArray(data_get($result, 'meta.diagnostics'));
        $this->assertIsInt((int) data_get($result, 'meta.backtracks', 0));
        $this->assertIsInt((int) data_get($result, 'meta.recursive_steps', 0));
    }

    public function test_backtracking_zero_domain_precheck_returns_clear_diagnostics(): void
    {
        $this->actingAsCoordinator();

        ScheduleSettingConstraint::query()
            ->where('constraint_key', 'instructor_availability')
            ->update(['enabled' => true]);

        $semesterId = (string) Semester::query()->value('id');
        $csi = CourseSectionInstructor::query()
            ->whereHas('section', fn ($q) => $q->where('section_type', 'room'))
            ->with('section')
            ->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();

        $job = $this->queueAndProcess([
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
            'baseDraft' => [
                'sessions' => [
                    [
                        'course_offering_id' => $offering->id,
                        'section_instructor_id' => $csi->id,
                    ],
                ],
            ],
            'instructor_availabilities' => [
                [
                    'instructor_id' => $csi->instructor_id,
                    'day_of_week' => 'fri',
                    'start' => '08:00',
                    'end' => '08:50',
                    'status' => 'preferred',
                ],
            ],
        ]);

        $this->assertSame('failed', $job->status);
        $result = (array) $job->result_payload;
        $this->assertFalse((bool) ($result['success'] ?? true));
        $this->assertStringContainsString('zero feasible candidates', (string) ($result['reason'] ?? ''));
        $this->assertSame('zero_domain_precheck', data_get($result, 'meta.termination_reason'));
        $this->assertIsArray(data_get($result, 'meta.diagnostics'));
    }

    public function test_backtracking_respects_hard_capacity_threshold_during_search(): void
    {
        $this->actingAsCoordinator();

        ScheduleSetting::query()->update(['capacity_threshold' => 40]);
        ScheduleSettingConstraint::query()
            ->where('constraint_key', 'capacity_threshold')
            ->update(['enabled' => true]);

        $semesterId = (string) Semester::query()->value('id');
        $csi = CourseSectionInstructor::query()
            ->whereHas('section', fn ($q) => $q
                ->where('section_type', 'room')
                ->where('capacity', '>=', 55)
                ->where('capacity', '<=', 65))
            ->with('section')
            ->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();

        $job = $this->queueAndProcess([
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
            'seed' => 42,
            'baseDraft' => [
                'sessions' => [
                    [
                        'course_offering_id' => $offering->id,
                        'section_instructor_id' => $csi->id,
                    ],
                ],
            ],
        ]);

        $this->assertSame('completed', $job->status);

        $result = (array) $job->result_payload;
        $this->assertTrue((bool) ($result['success'] ?? false));
        $this->assertSame(0, (int) data_get($result, 'meta.hard_violations', 0));
        $this->assertArrayNotHasKey('capacity_threshold', (array) data_get($result, 'meta.hard_breakdown', []));
    }

    public function test_backtracking_seeded_baseline_finishes_within_configured_limit_or_fails_gracefully(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = (string) Semester::query()->value('id');

        $startedAt = microtime(true);
        $result = $service->generate('backtracking', $semesterId, null, null, null, null, 42, null);
        $elapsed = microtime(true) - $startedAt;

        $limit = (float) config('optitime.backtracking.max_seconds');
        $this->assertLessThan($limit + 1.0, $elapsed, "Backtracking exceeded configured max_seconds ({$limit}).");

        if (! ($result['success'] ?? false)) {
            $this->assertNotNull(data_get($result, 'meta.termination_reason'));
            $this->assertIsArray(data_get($result, 'meta.diagnostics'));
        }
    }
}
