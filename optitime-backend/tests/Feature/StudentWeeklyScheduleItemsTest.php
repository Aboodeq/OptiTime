<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\Student;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentWeeklyScheduleItemsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_weekly_excludes_unassigned_sessions(): void
    {
        $semesterId = Semester::query()->value('id');
        $csi = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'published',
        ]);

        ScheduleSession::query()->create([
            'schedule_plan_id' => $plan->id,
            'room_id' => $roomId,
            'section_instructor_id' => $csi->id,
            'course_offering_id' => $offering->id,
            'day_value' => 'sun',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ]);

        $this->assertSame(0, ScheduleSessionStudent::query()->count());

        $student = User::query()->where('email', 'student1@optitime.local')->firstOrFail();
        Sanctum::actingAs($student);

        $response = $this->getJson('/api/student/weekly-schedule?semester_id='.$semesterId);
        $response->assertOk();
        $response->assertJsonCount(0, 'items');
    }

    public function test_student_weekly_includes_only_sessions_assigned_to_authenticated_student(): void
    {
        $semesterId = Semester::query()->value('id');
        $csi = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'published',
        ]);

        $assignedSession = ScheduleSession::query()->create([
            'schedule_plan_id' => $plan->id,
            'room_id' => $roomId,
            'section_instructor_id' => $csi->id,
            'course_offering_id' => $offering->id,
            'day_value' => 'mon',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        ScheduleSession::query()->create([
            'schedule_plan_id' => $plan->id,
            'room_id' => $roomId,
            'section_instructor_id' => $csi->id,
            'course_offering_id' => $offering->id,
            'day_value' => 'tue',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        $otherStudentSession = ScheduleSession::query()->create([
            'schedule_plan_id' => $plan->id,
            'room_id' => $roomId,
            'section_instructor_id' => $csi->id,
            'course_offering_id' => $offering->id,
            'day_value' => 'wed',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
        ]);

        $studentUser = User::query()->where('email', 'student1@optitime.local')->firstOrFail();
        $otherStudent = Student::query()
            ->where('user_id', '!=', $studentUser->id)
            ->first();

        if (! $otherStudent) {
            $otherStudentUser = User::query()->create([
                'role_id' => $studentUser->role_id,
                'department_id' => $studentUser->department_id,
                'full_name' => 'Other Test Student',
                'email' => 'other-student1@optitime.local',
                'password_hash' => $studentUser->password_hash,
                'is_active' => true,
            ]);

            $otherStudent = Student::query()->create([
                'user_id' => $otherStudentUser->id,
                'university_number' => 'UT-OTHER-0001',
                'completed_hours' => 0,
                'year_level' => 1,
                'study_status' => 'regular',
            ]);
        }

        ScheduleSessionStudent::query()->create([
            'schedule_session_id' => $assignedSession->id,
            'student_id' => $studentUser->id,
        ]);

        ScheduleSessionStudent::query()->create([
            'schedule_session_id' => $otherStudentSession->id,
            'student_id' => $otherStudent->user_id,
        ]);

        Sanctum::actingAs($studentUser);

        $response = $this->getJson('/api/student/weekly-schedule?semester_id='.$semesterId);
        $response->assertOk();
        $response->assertJsonCount(1, 'items');
        $response->assertJsonPath('items.0.id', $assignedSession->id);
        $response->assertJsonPath('items.0.start_time', '09:00');
        $response->assertJsonMissingPath('items.0.students');
    }
}

