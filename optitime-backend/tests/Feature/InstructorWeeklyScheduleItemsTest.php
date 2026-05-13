<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InstructorWeeklyScheduleItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_instructor_weekly_includes_students_list_for_each_session(): void
    {
        $instructorUser = User::query()->where('email', 'instructor1@optitime.local')->firstOrFail();
        Sanctum::actingAs($instructorUser);

        $semesterId = Semester::query()->value('id');
        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'published',
        ]);

        $csi = CourseSectionInstructor::query()
            ->whereHas('instructor', fn ($q) => $q->where('user_id', $instructorUser->id))
            ->with('section.course')
            ->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $session = ScheduleSession::query()->create([
            'schedule_plan_id' => $plan->id,
            'room_id' => $roomId,
            'section_instructor_id' => $csi->id,
            'course_offering_id' => $offering->id,
            'day_value' => 'sun',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $studentUser = User::query()->where('email', 'student1@optitime.local')->firstOrFail();
        ScheduleSessionStudent::query()->create([
            'schedule_session_id' => $session->id,
            'student_id' => $studentUser->id,
            'assignment_source' => 'auto',
            'oral' => 0,
            'lab' => 0,
            'midterm' => 0,
            'final' => 0,
            'total' => 0,
            'letter_grade' => 'F',
            'grade_entered_at' => null,
        ]);

        $response = $this->getJson('/api/instructor/weekly-schedule?semester_id='.$semesterId);
        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.id', $session->id);
        $response->assertJsonCount(1, 'items.0.students');
        $response->assertJsonPath('items.0.students.0.id', $studentUser->id);
    }
}

