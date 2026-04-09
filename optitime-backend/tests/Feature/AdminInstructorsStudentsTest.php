<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Specialization;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInstructorsStudentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function adminToken(): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@optitime.local',
            'password' => 'password',
        ]);
        $response->assertOk();

        return $response->json('token');
    }

    public function test_instructors_crud(): void
    {
        $token = $this->adminToken();
        $specId = Specialization::query()->value('id');
        $deptId = Department::query()->value('id');

        $list = $this->getJson('/api/admin/instructors', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $list->assertOk();
        $initialCount = count($list->json());

        $create = $this->postJson('/api/admin/instructors', [
            'full_name' => 'New Instructor',
            'email' => 'new.instructor@test.local',
            'password' => 'password12',
            'department_id' => $deptId,
            'specialization_id' => $specId,
            'min_work_hours_per_week' => 4,
            'max_work_hours_per_week' => 20,
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $create->assertStatus(201);
        $insId = $create->json('id');

        $show = $this->getJson('/api/admin/instructors/'.$insId, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $show->assertOk();
        $show->assertJsonPath('email', 'new.instructor@test.local');

        $update = $this->putJson('/api/admin/instructors/'.$insId, [
            'full_name' => 'Renamed Instructor',
            'max_work_hours_per_week' => 18,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $update->assertOk();
        $update->assertJsonPath('name', 'Renamed Instructor');

        $del = $this->deleteJson('/api/admin/instructors/'.$insId, [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $del->assertOk();

        $after = $this->getJson('/api/admin/instructors', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $after->assertOk();
        $this->assertCount($initialCount, $after->json());
    }

    public function test_students_crud(): void
    {
        $token = $this->adminToken();
        $deptId = Department::query()->value('id');

        $list = $this->getJson('/api/admin/students', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $list->assertOk();
        $initialCount = count($list->json());

        $create = $this->postJson('/api/admin/students', [
            'full_name' => 'New Student',
            'email' => 'new.student@test.local',
            'password' => 'password12',
            'department_id' => $deptId,
            'completed_hours' => 0,
            'year_level' => 2,
            'study_status' => 'regular',
            'is_active' => true,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $create->assertStatus(201);
        $userId = $create->json('user_id');
        $this->assertNotEmpty($create->json('university_number'));

        $show = $this->getJson('/api/admin/students/'.$userId, [
            'Authorization' => 'Bearer '.$token,
        ]);
        $show->assertOk();

        $update = $this->putJson('/api/admin/students/'.$userId, [
            'year_level' => 3,
            'completed_hours' => 30,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $update->assertOk();
        $update->assertJsonPath('year_level', 3);

        $del = $this->deleteJson('/api/admin/students/'.$userId, [], [
            'Authorization' => 'Bearer '.$token,
        ]);
        $del->assertOk();

        $after = $this->getJson('/api/admin/students', [
            'Authorization' => 'Bearer '.$token,
        ]);
        $after->assertOk();
        $this->assertCount($initialCount, $after->json());
    }
}
