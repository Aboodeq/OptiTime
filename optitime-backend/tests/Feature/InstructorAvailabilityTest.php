<?php

namespace Tests\Feature;

use App\Models\InstructorAvailabilityCell;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function instructorToken(string $email): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $email,
            'password' => 'password',
        ]);
        $response->assertOk();
        $response->assertJsonPath('user.email', $email);

        return $response->json('token');
    }

    protected function user(string $email): User
    {
        return User::query()->where('email', $email)->firstOrFail();
    }

    public function test_login_includes_instructor_relation(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'instructor1@optitime.local',
            'password' => 'password',
        ]);
        $response->assertOk();
        $this->assertNotEmpty(data_get($response->json(), 'user.instructor.id'));
        $this->assertNotEmpty(data_get($response->json(), 'user.instructor.user_id'));
        $response->assertJsonStructure([
            'user' => [
                'instructor' => ['id', 'user_id', 'min_work_hours_per_week', 'max_work_hours_per_week'],
            ],
        ]);
    }

    public function test_crud_scoped_to_authenticated_instructor(): void
    {
        $u1 = $this->user('instructor1@optitime.local');
        $u2 = $this->user('instructor2@optitime.local');
        $this->assertNotSame($u1->id, $u2->id);

        Sanctum::actingAs($u1);

        $this->getJson('/api/instructor/availabilities')->assertOk()->assertJsonCount(0);

        $create = $this->postJson('/api/instructor/availabilities', [
            'day_of_week' => 'mon',
            'start_time' => '09:00',
            'end_time' => '10:00',
            'status' => 'preferred',
        ]);
        $create->assertCreated();
        $cellId = $create->json('id');
        $this->assertNotEmpty($cellId);

        $this->getJson('/api/instructor/availabilities')->assertOk()->assertJsonCount(1);

        Sanctum::actingAs($u2);
        $this->getJson('/api/instructor/availabilities')->assertOk()->assertJsonCount(0);

        Sanctum::actingAs($u1);
        $this->putJson('/api/instructor/availabilities/'.$cellId, ['status' => 'unavailable'])
            ->assertOk()
            ->assertJsonPath('status', 'unavailable');

        $this->deleteJson('/api/instructor/availabilities/'.$cellId)->assertOk()->assertJsonPath('deleted', true);

        $this->getJson('/api/instructor/availabilities')->assertOk()->assertJsonCount(0);

        $this->assertSame(0, InstructorAvailabilityCell::query()->count());
    }

    public function test_instructor_two_cannot_update_instructor_one_cell(): void
    {
        $u1 = $this->user('instructor1@optitime.local');
        $u2 = $this->user('instructor2@optitime.local');

        Sanctum::actingAs($u1);
        $create = $this->postJson('/api/instructor/availabilities', [
            'day_of_week' => 'tue',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);
        $create->assertCreated();
        $cellId = $create->json('id');

        Sanctum::actingAs($u2);
        $this->putJson('/api/instructor/availabilities/'.$cellId, ['status' => 'unavailable'])->assertNotFound();
    }

    public function test_get_user_endpoint_loads_instructor(): void
    {
        $user = $this->user('instructor1@optitime.local');
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/user');
        $response->assertOk();
        $response->assertJsonPath('id', $user->id);
        $this->assertNotEmpty(data_get($response->json(), 'instructor.id'));
    }

    public function test_availability_grid_context_returns_minimal_schedule_fields(): void
    {
        $this->getJson('/api/instructor/availability-grid-context')->assertUnauthorized();

        $student = $this->user('student1@optitime.local');
        Sanctum::actingAs($student);
        $this->getJson('/api/instructor/availability-grid-context')->assertForbidden();

        $instructor = $this->user('instructor1@optitime.local');
        Sanctum::actingAs($instructor);
        $response = $this->getJson('/api/instructor/availability-grid-context');
        $response->assertOk();
        $response->assertJsonStructure([
            'day_start',
            'day_end',
            'slot_minutes',
            'gap_minutes',
            'max_daily_lectures',
            'study_days' => [
                '*' => ['value', 'enabled'],
            ],
            'break_times' => [
                '*' => ['key', 'start', 'end', 'enabled'],
            ],
        ]);
        $response->assertJsonMissingPath('capacity_threshold');
        $response->assertJsonMissingPath('hard_constraints');
    }
}

