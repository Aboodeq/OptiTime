<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\Semester;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleGenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_backtracking_generates_sessions(): void
    {
        $semesterId = Semester::query()->value('id');
        $response = $this->postJson('/api/schedule/generate', [
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('algorithm', 'backtracking');
        $data = $response->json('sessions');
        $this->assertCount(3, $data);
        $this->assertArrayHasKey('day', $data[0]);
        $this->assertArrayHasKey('start', $data[0]);
        $this->assertArrayHasKey('end', $data[0]);
        $this->assertArrayHasKey('course_id', $data[0]);
        $this->assertArrayHasKey('section_instructor_id', $data[0]);
        $this->assertArrayHasKey('course_offering_id', $data[0]);
        $this->assertArrayHasKey('room_id', $data[0]);
    }

    public function test_genetic_returns_sessions_and_meta(): void
    {
        $semesterId = Semester::query()->value('id');
        $response = $this->postJson('/api/schedule/generate', [
            'algorithm' => 'genetic',
            'semester_id' => $semesterId,
            'seed' => 42,
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('algorithm', 'genetic');
        $this->assertArrayHasKey('success', $response->json());
        $this->assertArrayHasKey('meta', $response->json());
        $this->assertArrayHasKey('generations', $response->json('meta'));
    }

    public function test_base_draft_fixed_session_respected(): void
    {
        $semesterId = Semester::query()->value('id');
        $csi = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $csi2 = CourseSectionInstructor::query()->where('id', '!=', $csi->id)->with('section.course')->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $offering2 = CourseOffering::query()
            ->where('course_id', $csi2->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = \App\Models\Room::query()->where('type', '!=', 'lab')->value('id');

        $response = $this->postJson('/api/schedule/generate', [
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
            'baseDraft' => [
                'sessions' => [
                    [
                        'course_id' => $csi->section->course_id,
                        'course_section_id' => $csi->section_id,
                        'section_id' => $csi->section_id,
                        'instructor_id' => $csi->instructor_id,
                        'course_offering_id' => $offering->id,
                        'section_instructor_id' => $csi->id,
                        'enrollment' => 10,
                        'is_lab' => false,
                        'day' => 'sunday',
                        'start' => '08:00',
                        'room_id' => $roomId,
                    ],
                    [
                        'course_id' => $csi2->section->course_id,
                        'course_section_id' => $csi2->section_id,
                        'instructor_id' => $csi2->instructor_id,
                        'course_offering_id' => $offering2->id,
                        'section_instructor_id' => $csi2->id,
                        'enrollment' => 15,
                        'is_lab' => true,
                        'course_code' => 'X',
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $sessions = $response->json('sessions');
        $fixed = collect($sessions)->firstWhere('section_instructor_id', $csi->id);
        $this->assertNotNull($fixed);
        $this->assertSame('sun', $fixed['day']);
        $this->assertSame('08:00', $fixed['start']);
        $this->assertSame($roomId, $fixed['room_id']);
    }
}
