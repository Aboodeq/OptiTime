<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CoursePrerequisite;
use App\Models\CourseSectionInstructor;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CoordinatorScheduleSessionsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_sync_sessions_on_draft_plan(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $semesterId = Semester::query()->value('id');
        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'draft',
        ]);

        $csi = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $response = $this->putJson("/api/coordinator/schedules/{$plan->id}/sessions", [
            'sessions' => [
                [
                    'room_id' => $roomId,
                    'section_instructor_id' => $csi->id,
                    'course_offering_id' => $offering->id,
                    'day' => 'sun',
                    'start' => '08:00',
                    'end' => '09:00',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertCount(1, ScheduleSession::query()->where('schedule_plan_id', $plan->id)->get());
        $this->assertGreaterThan(0, ScheduleSessionStudent::query()->count());
    }

    public function test_sync_rejects_published_plan(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $semesterId = Semester::query()->value('id');
        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'published',
        ]);

        $csi = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $offering = CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $response = $this->putJson("/api/coordinator/schedules/{$plan->id}/sessions", [
            'sessions' => [
                [
                    'room_id' => $roomId,
                    'section_instructor_id' => $csi->id,
                    'course_offering_id' => $offering->id,
                    'day' => 'sun',
                    'start' => '08:00',
                    'end' => '09:00',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_sync_rejects_room_overlap(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $semesterId = Semester::query()->value('id');
        $plan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'draft',
        ]);

        $csi1 = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $csi2 = CourseSectionInstructor::query()->where('id', '!=', $csi1->id)->with('section.course')->firstOrFail();
        $offering1 = CourseOffering::query()
            ->where('course_id', $csi1->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $offering2 = CourseOffering::query()
            ->where('course_id', $csi2->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $roomId = Room::query()->where('type', '!=', 'lab')->value('id');

        $response = $this->putJson("/api/coordinator/schedules/{$plan->id}/sessions", [
            'sessions' => [
                [
                    'room_id' => $roomId,
                    'section_instructor_id' => $csi1->id,
                    'course_offering_id' => $offering1->id,
                    'day' => 'sun',
                    'start' => '08:00',
                    'end' => '09:00',
                ],
                [
                    'room_id' => $roomId,
                    'section_instructor_id' => $csi2->id,
                    'course_offering_id' => $offering2->id,
                    'day' => 'sun',
                    'start' => '08:30',
                    'end' => '09:30',
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    public function test_sync_assigns_only_students_with_passed_prerequisites_and_without_time_conflicts(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);

        $semesterId = Semester::query()->value('id');
        $draftPlan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'draft',
        ]);

        $csiA = CourseSectionInstructor::query()->with('section.course')->firstOrFail();
        $csiB = CourseSectionInstructor::query()
            ->where('id', '!=', $csiA->id)
            ->where('instructor_id', '!=', $csiA->instructor_id)
            ->with('section.course')
            ->firstOrFail();

        $offeringA = CourseOffering::query()
            ->where('course_id', $csiA->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
        $offeringB = CourseOffering::query()
            ->where('course_id', $csiB->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();

        CoursePrerequisite::query()->firstOrCreate([
            'course_id' => $csiB->section->course_id,
            'prerequisite_course_id' => $csiA->section->course_id,
        ]);

        $studentUser = User::query()->where('email', 'student@optitime.local')->firstOrFail();
        $student = Student::query()->where('user_id', $studentUser->id)->firstOrFail();

        $otherStudentUser = User::query()->create([
            'role_id' => $studentUser->role_id,
            'department_id' => $studentUser->department_id,
            'full_name' => 'Blocked Student',
            'email' => 'blocked-student@optitime.local',
            'password_hash' => $studentUser->password_hash,
            'is_active' => true,
        ]);
        $otherStudent = Student::query()->create([
            'user_id' => $otherStudentUser->id,
            'university_number' => 'UT-BLOCK-0001',
            'completed_hours' => 0,
            'year_level' => 1,
            'study_status' => 'regular',
        ]);

        $historyPlan = SemesterSchedulePlan::query()->create([
            'semester_id' => $semesterId,
            'status' => 'published',
        ]);

        $historySession = ScheduleSession::query()->create([
            'schedule_plan_id' => $historyPlan->id,
            'room_id' => Room::query()->where('type', '!=', 'lab')->value('id'),
            'section_instructor_id' => $csiA->id,
            'course_offering_id' => $offeringA->id,
            'day_value' => 'sun',
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        ScheduleSessionStudent::query()->create([
            'schedule_session_id' => $historySession->id,
            'student_id' => $student->user_id,
            'assignment_source' => 'manual',
            'oral' => 20,
            'lab' => 20,
            'midterm' => 20,
            'final' => 20,
            'total' => 80,
            'letter_grade' => 'B',
            'grade_entered_at' => now(),
        ]);

        ScheduleSessionStudent::query()->create([
            'schedule_session_id' => $historySession->id,
            'student_id' => $otherStudent->user_id,
            'assignment_source' => 'manual',
            'oral' => 10,
            'lab' => 10,
            'midterm' => 10,
            'final' => 10,
            'total' => 40,
            'letter_grade' => 'F',
            'grade_entered_at' => now(),
        ]);

        $roomOne = Room::query()->where('type', '!=', 'lab')->orderBy('capacity')->value('id');
        $roomTwo = Room::query()->where('type', '!=', 'lab')->where('id', '!=', $roomOne)->value('id');

        $response = $this->putJson("/api/coordinator/schedules/{$draftPlan->id}/sessions", [
            'sessions' => [
                [
                    'room_id' => $roomOne,
                    'section_instructor_id' => $csiB->id,
                    'course_offering_id' => $offeringB->id,
                    'day' => 'sun',
                    'start' => '08:00',
                    'end' => '09:00',
                ],
                [
                    'room_id' => $roomTwo,
                    'section_instructor_id' => $csiA->id,
                    'course_offering_id' => $offeringA->id,
                    'day' => 'sun',
                    'start' => '08:00',
                    'end' => '09:00',
                ],
            ],
        ]);

        $response->assertOk();

        $sessions = ScheduleSession::query()
            ->where('schedule_plan_id', $draftPlan->id)
            ->with('courseOffering')
            ->get();

        $sessionForCourseB = $sessions->firstWhere('courseOffering.course_id', $csiB->section->course_id);
        $this->assertNotNull($sessionForCourseB);

        $assignedEligible = ScheduleSessionStudent::query()
            ->where('schedule_session_id', $sessionForCourseB->id)
            ->where('student_id', $student->user_id)
            ->exists();
        $assignedBlocked = ScheduleSessionStudent::query()
            ->where('schedule_session_id', $sessionForCourseB->id)
            ->where('student_id', $otherStudent->user_id)
            ->exists();

        $this->assertTrue($assignedEligible);
        $this->assertFalse($assignedBlocked);

        $sameTimeAssignedCount = ScheduleSessionStudent::query()
            ->where('student_id', $student->user_id)
            ->whereHas('session', function ($q) use ($draftPlan): void {
                $q->where('schedule_plan_id', $draftPlan->id)
                    ->where('day_value', 'sun')
                    ->where('start_time', '08:00:00')
                    ->where('end_time', '09:00:00');
            })
            ->count();

        $this->assertSame(1, $sameTimeAssignedCount);
    }
}
