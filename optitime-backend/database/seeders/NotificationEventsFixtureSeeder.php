<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\Role;
use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\ScheduleSessionStudent;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class NotificationEventsFixtureSeeder extends Seeder
{
    public function run(): void
    {
        $semester = Semester::query()->where('is_active', true)->first() ?? Semester::query()->first();
        if (! $semester) {
            $this->command?->warn('No semester found. Run base seeder first.');

            return;
        }

        $coordinator = $this->ensureCoordinator();
        $instructorUser = $this->ensureInstructorUser();
        $studentUser = $this->ensureStudentUser();
        $room = Room::query()->where('status', 'available')->orderBy('name_en')->first() ?? Room::query()->first();
        if (! $room) {
            $this->command?->warn('No room found. Run base seeder first.');

            return;
        }

        $sectionInstructor = $this->ensureSectionInstructorForSemester((string) $semester->id, (string) $instructorUser->instructor->id);
        if (! $sectionInstructor) {
            $this->command?->warn('Unable to prepare section instructor fixture.');

            return;
        }

        $courseOffering = CourseOffering::query()
            ->where('semester_id', (string) $semester->id)
            ->where('course_id', (string) $sectionInstructor->section->course_id)
            ->first();

        if (! $courseOffering) {
            $this->command?->warn('Unable to find matching course offering for fixture.');

            return;
        }

        $plan = SemesterSchedulePlan::query()->updateOrCreate(
            [
                'semester_id' => (string) $semester->id,
                'notes' => 'notification-fixture-plan',
            ],
            [
                'status' => 'published',
                'selected_algorithm' => 'backtracking',
                'generated_at' => now(),
            ]
        );

        $session = ScheduleSession::query()->firstOrCreate(
            [
                'schedule_plan_id' => (string) $plan->id,
                'section_instructor_id' => (string) $sectionInstructor->id,
                'course_offering_id' => (string) $courseOffering->id,
                'day_value' => 'mon',
                'start_time' => '09:00:00',
                'end_time' => '09:50:00',
            ],
            [
                'room_id' => (string) $room->id,
                'conflict_state' => 'none',
                'grades_entry_status' => 'open',
            ]
        );

        ScheduleSessionStudent::query()->firstOrCreate(
            [
                'schedule_session_id' => (string) $session->id,
                'student_id' => (string) $studentUser->id,
            ],
            [
                'assignment_source' => 'manual',
                'oral' => 0,
                'lab' => 0,
                'midterm' => 0,
                'final' => 0,
                'total' => 0,
                'letter_grade' => 'F',
            ]
        );

        $this->command?->info('Notification fixture ready.');
        $this->command?->line('Coordinator: coordinator@optitime.local / password');
        $this->command?->line('Instructor: instructor1@optitime.local / password');
        $this->command?->line('Student: student1@optitime.local / password');
        $this->command?->line("Fixture plan ID: {$plan->id}");
        $this->command?->line("Fixture session ID: {$session->id}");
        $this->command?->line("Fixture student_id for grade API: {$studentUser->id}");
        $this->command?->line("Fixture room_id: {$room->id}");
        $this->command?->line("Fixture section_instructor_id: {$sectionInstructor->id}");
        $this->command?->line("Fixture course_offering_id: {$courseOffering->id}");
        $this->command?->line("Fixture day/start/end: mon 09:00:00 -> 09:50:00");
    }

    private function ensureCoordinator(): User
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->first();
        if ($user) {
            return $user;
        }

        $role = Role::query()->where('code', 'coordinator')->firstOrFail();
        $dept = Department::query()->first();
        $user = User::query()->create([
            'role_id' => (string) $role->id,
            'department_id' => $dept?->id,
            'full_name' => 'Coordinator User',
            'email' => 'coordinator@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->firstOrCreate(['user_id' => (string) $user->id]);

        return $user;
    }

    private function ensureInstructorUser(): User
    {
        $existing = User::query()->where('email', 'instructor1@optitime.local')->with('instructor')->first();
        if ($existing && $existing->instructor) {
            return $existing;
        }

        $role = Role::query()->where('code', 'instructor')->firstOrFail();
        $dept = Department::query()->first();
        $user = $existing ?? User::query()->create([
            'role_id' => (string) $role->id,
            'department_id' => $dept?->id,
            'full_name' => 'Dr. Instructor 1',
            'email' => 'instructor1@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->firstOrCreate(['user_id' => (string) $user->id]);
        if (! $user->instructor) {
            $spec = Specialization::query()->first();
            if (! $spec) {
                $spec = Specialization::query()->create([
                    'code' => 'notif-spec',
                    'name_ar' => 'اختصاص إشعارات',
                    'name_en' => 'Notifications Specialty',
                    'is_active' => true,
                ]);
            }
            Instructor::query()->create([
                'user_id' => (string) $user->id,
                'specialization_id' => (string) $spec->id,
                'min_work_hours_per_week' => 0,
                'max_work_hours_per_week' => 20,
            ]);
        }

        return $user->fresh(['instructor']);
    }

    private function ensureStudentUser(): User
    {
        $existing = User::query()->where('email', 'student1@optitime.local')->with('student')->first();
        if ($existing && $existing->student) {
            return $existing;
        }

        $role = Role::query()->where('code', 'student')->firstOrFail();
        $dept = Department::query()->first();
        $user = $existing ?? User::query()->create([
            'role_id' => (string) $role->id,
            'department_id' => $dept?->id,
            'full_name' => 'Student 1',
            'email' => 'student1@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->firstOrCreate(['user_id' => (string) $user->id]);
        if (! $user->student) {
            Student::query()->create([
                'user_id' => (string) $user->id,
                'university_number' => 'STU-00001',
                'completed_hours' => 0,
                'year_level' => 1,
                'study_status' => 'regular',
            ]);
        }

        return $user->fresh(['student']);
    }

    private function ensureSectionInstructorForSemester(string $semesterId, string $instructorId): ?CourseSectionInstructor
    {
        $existing = CourseSectionInstructor::query()
            ->where('instructor_id', $instructorId)
            ->whereHas('section.course.offerings', fn ($q) => $q->where('semester_id', $semesterId)->where('is_active', true))
            ->with('section')
            ->first();
        if ($existing) {
            return $existing;
        }

        $departmentId = Department::query()->value('id');
        if (! $departmentId) {
            return null;
        }
        $course = Course::query()->firstOrCreate(
            ['department_id' => $departmentId, 'code' => 'NOTIF-101'],
            [
                'name_ar' => 'مقرر الإشعارات',
                'name_en' => 'Notifications Course',
                'required_hours' => 3,
                'has_lab_component' => false,
            ]
        );
        $section = CourseSection::query()->firstOrCreate(
            ['course_id' => (string) $course->id, 'section_name' => 'Section N1'],
            [
                'section_type' => 'room',
                'capacity' => 40,
            ]
        );
        CourseOffering::query()->firstOrCreate(
            ['course_id' => (string) $course->id, 'semester_id' => $semesterId],
            ['is_active' => true]
        );

        return CourseSectionInstructor::query()->firstOrCreate(
            ['section_id' => (string) $section->id, 'instructor_id' => $instructorId]
        );
    }
}
