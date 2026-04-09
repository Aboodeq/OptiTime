<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CoursePrerequisite;
use App\Models\Department;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseExtrasTest extends TestCase
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

    public function test_course_create_persists_constraints_and_prerequisites(): void
    {
        $token = $this->adminToken();
        $deptId = Department::query()->value('id');
        $otherCourse = Course::query()->firstOrFail();

        $create = $this->postJson('/api/admin/courses', [
            'department_id' => $deptId,
            'code' => 'TST-999',
            'name_ar' => 'اختبار',
            'name_en' => 'Test Course',
            'required_hours' => 3,
            'has_lab_component' => true,
            'room_consumed_hours' => 2,
            'lab_consumed_hours' => 2,
            'min_student_year_level' => 2,
            'max_student_year_level' => 4,
            'prerequisite_course_ids' => [$otherCourse->id],
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $create->assertStatus(201);
        $courseId = $create->json('id');
        $create->assertJsonPath('course_constraint.min_student_year_level', 2);
        $create->assertJsonPath('course_constraint.max_student_year_level', 4);

        $this->assertDatabaseHas('course_constraints', [
            'course_id' => $courseId,
            'min_student_year_level' => 2,
            'max_student_year_level' => 4,
        ]);
        $this->assertDatabaseHas('course_prerequisites', [
            'course_id' => $courseId,
            'prerequisite_course_id' => $otherCourse->id,
        ]);
    }

    public function test_course_update_can_clear_prerequisites(): void
    {
        $token = $this->adminToken();
        $course = Course::query()->firstOrFail();

        $this->putJson('/api/admin/courses/'.$course->id, [
            'min_student_year_level' => 1,
            'max_student_year_level' => 5,
            'prerequisite_course_ids' => [],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        CoursePrerequisite::query()->create([
            'course_id' => $course->id,
            'prerequisite_course_id' => Course::query()->where('id', '!=', $course->id)->value('id'),
        ]);

        $this->putJson('/api/admin/courses/'.$course->id, [
            'prerequisite_course_ids' => [],
        ], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->assertSame(0, CoursePrerequisite::query()->where('course_id', $course->id)->count());
    }
}
