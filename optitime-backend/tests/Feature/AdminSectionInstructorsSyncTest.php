<?php

namespace Tests\Feature;

use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Models\Instructor;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSectionInstructorsSyncTest extends TestCase
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

    public function test_admin_can_sync_section_instructors_and_response_includes_instructors(): void
    {
        $token = $this->adminToken();
        $section = CourseSection::query()->firstOrFail();
        $instructorIds = Instructor::query()->take(2)->pluck('id')->values()->all();
        $this->assertNotEmpty($instructorIds);

        CourseSectionInstructor::query()->where('section_id', $section->id)->delete();

        $response = $this->postJson('/api/admin/sections/'.$section->id.'/instructors', [
            'instructor_ids' => $instructorIds,
        ], [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk();
        $response->assertJsonPath('id', $section->id);
        $ids = collect($response->json('instructors'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing($instructorIds, $ids);

        $this->assertSame(
            count($instructorIds),
            CourseSectionInstructor::query()->where('section_id', $section->id)->count()
        );
    }
}
