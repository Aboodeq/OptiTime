<?php

namespace Tests\Feature;

use App\Models\Semester;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WeeklyScheduleBoardContextTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_instructor_weekly_includes_board_without_settings_id(): void
    {
        $user = User::query()->where('email', 'instructor1@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);
        $semesterId = Semester::query()->value('id');

        $response = $this->getJson('/api/instructor/weekly-schedule?semester_id='.$semesterId);
        $response->assertOk();
        $response->assertJsonStructure([
            'semester' => ['id', 'name', 'code', 'is_active'],
            'board' => [
                'day_start',
                'day_end',
                'slot_minutes',
                'gap_minutes',
                'study_days',
                'break_times',
            ],
        ]);
        $response->assertJsonMissingPath('board.settings_id');
    }

    public function test_instructor_weekly_resolves_active_semester_without_query_param(): void
    {
        $user = User::query()->where('email', 'instructor1@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/instructor/weekly-schedule');
        $response->assertOk();
        $response->assertJsonPath('semester.id', Semester::query()->where('is_active', true)->value('id'));
    }

    public function test_student_weekly_includes_board_without_settings_id(): void
    {
        $user = User::query()->where('email', 'student1@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);
        $semesterId = Semester::query()->value('id');

        $response = $this->getJson('/api/student/weekly-schedule?semester_id='.$semesterId);
        $response->assertOk();
        $response->assertJsonPath('board.day_start', '08:00');
        $response->assertJsonMissingPath('board.settings_id');
    }

    public function test_coordinator_schedule_board_context_includes_settings_id(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/coordinator/schedule-board-context');
        $response->assertOk();
        $response->assertJsonStructure([
            'settings_id',
            'day_start',
            'study_days',
            'break_times',
        ]);
        $this->assertNotNull($response->json('settings_id'));
    }
}

