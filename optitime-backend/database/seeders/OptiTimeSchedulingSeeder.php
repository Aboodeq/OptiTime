<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseSection;
use App\Models\CourseSectionInstructor;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Instructor;
use App\Models\Resource;
use App\Models\Role;
use App\Models\Room;
use App\Models\ScheduleSetting;
use App\Models\ScheduleSettingBreakTime;
use App\Models\ScheduleSettingConstraint;
use App\Models\ScheduleSettingStudyDay;
use App\Models\Semester;
use App\Models\Specialization;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OptiTimeSchedulingSeeder extends Seeder
{
    public function run(): void
    {
        $faculty = Faculty::query()->create(['code' => 'informatics', 'name_ar' => 'هندسة معلوماتية', 'name_en' => 'Informatics', 'graduation_hours' => 160, 'studying_level' => 4, 'color' => '#4361ee', 'is_active' => true]);
        $dept = Department::query()->create(['faculty_id' => $faculty->id, 'code' => 'software', 'name_ar' => 'البرمجيات', 'name_en' => 'Software', 'is_active' => true]);
        $spec = Specialization::query()->create(['code' => 'se', 'name_ar' => 'هندسة برمجيات', 'name_en' => 'Software Engineering', 'is_active' => true]);
        $semester = Semester::query()->create(['code' => '2026-s1', 'name' => 'Spring 2026', 'academic_year' => '2025-2026', 'start_date' => '2026-01-01', 'end_date' => '2026-06-30', 'is_active' => true]);

        $ss = ScheduleSetting::query()->create([
            'day_start' => '08:00',
            'day_end' => '18:00',
            'slot_minutes' => 50,
            'gap_minutes' => 10,
            'max_daily_lectures' => 5,
            'capacity_threshold' => 90,
        ]);

        foreach (['sun', 'mon', 'tue', 'wed', 'thu'] as $d) {
            ScheduleSettingStudyDay::query()->create([
                'schedule_setting_id' => $ss->id,
                'day_value' => $d,
                'enabled' => true,
            ]);
        }

        ScheduleSettingBreakTime::query()->create([
            'schedule_setting_id' => $ss->id,
            'break_key' => 'lunch_break',
            'start_time' => '12:00',
            'end_time' => '13:00',
            'enabled' => true,
        ]);

        $hard = [
            'no_instructor_overlap' => true,
            'no_room_overlap' => true,
            'no_section_overlap' => true,
            'room_capacity' => true,
            'room_status_available' => true,
            'lab_for_lab' => true,
            'working_hours' => true,
            'instructor_availability' => false,
            'capacity_threshold' => false,
        ];
        foreach ($hard as $key => $on) {
            ScheduleSettingConstraint::query()->create([
                'schedule_setting_id' => $ss->id,
                'category' => 'hard',
                'constraint_key' => $key,
                'enabled' => $on,
                'weight' => 1,
            ]);
        }

        $soft = [
            'instructor_preferences' => [1, false],
            'load_balance' => [3, true],
            'avoid_back_to_back' => [5, true],
            'student_gaps' => [2, false],
            'morning_preference' => [2, true],
            'department_proximity' => [1, false],
            'max_daily_lectures' => [4, true],
        ];
        foreach ($soft as $key => [$w, $en]) {
            ScheduleSettingConstraint::query()->create([
                'schedule_setting_id' => $ss->id,
                'category' => 'soft',
                'constraint_key' => $key,
                'enabled' => $en,
                'weight' => $w,
            ]);
        }

        Resource::query()->create(['name_ar' => 'جهاز عرض', 'name_en' => 'Projector', 'type' => 'projector', 'quantity' => 24, 'status' => 'available']);
        Resource::query()->create(['name_ar' => 'حاسوب محمول', 'name_en' => 'Laptop', 'type' => 'device', 'quantity' => 24, 'status' => 'available']);

        $rooms = [];
        for ($i = 1; $i <= 10; $i++) {
            $rooms[] = Room::query()->create(['name_ar' => "قاعة كبيرة {$i}", 'name_en' => "Large Hall {$i}", 'type' => 'hall', 'capacity' => 130 + ($i % 3) * 20, 'status' => 'available']);
        }
        for ($i = 1; $i <= 14; $i++) {
            $rooms[] = Room::query()->create(['name_ar' => "قاعة صف {$i}", 'name_en' => "Class {$i}", 'type' => 'class', 'capacity' => 50 + ($i % 4) * 10, 'status' => 'available']);
        }
        for ($i = 1; $i <= 8; $i++) {
            $rooms[] = Room::query()->create(['name_ar' => "مختبر {$i}", 'name_en' => "Lab {$i}", 'type' => 'lab', 'capacity' => 35 + ($i % 3) * 5, 'status' => 'available']);
        }

        $courses = [];
        for ($i = 1; $i <= 22; $i++) {
            $hasLab = $i % 3 === 0;
            $courses[] = Course::query()->create([
                'department_id' => $dept->id,
                'code' => 'CS'.str_pad((string) (100 + $i), 3, '0', STR_PAD_LEFT),
                'name_ar' => "مقرر {$i}",
                'name_en' => "Course {$i}",
                'required_hours' => $hasLab ? 2 : 3,
                'has_lab_component' => $hasLab,
            ]);
        }

        $sections = [];
        foreach ($courses as $courseIndex => $course) {
            $sections[] = CourseSection::query()->create([
                'course_id' => $course->id,
                'section_name' => 'Section A',
                'section_type' => 'room',
                'capacity' => 45 + ($courseIndex % 5) * 10,
            ]);
            $sections[] = CourseSection::query()->create([
                'course_id' => $course->id,
                'section_name' => 'Section B',
                'section_type' => $course->has_lab_component ? 'lab' : 'room',
                'capacity' => $course->has_lab_component ? 30 : (45 + (($courseIndex + 2) % 5) * 10),
            ]);
            CourseOffering::query()->create([
                'course_id' => $course->id,
                'semester_id' => $semester->id,
                'is_active' => true,
            ]);
        }

        $role = fn (string $c) => Role::query()->where('code', $c)->firstOrFail();

        $admin = User::query()->create([
            'role_id' => $role('admin')->id,
            'department_id' => null,
            'full_name' => 'Admin User',
            'email' => 'admin@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->create(['user_id' => $admin->id]);

        $coord = User::query()->create([
            'role_id' => $role('coordinator')->id,
            'department_id' => $dept->id,
            'full_name' => 'Coordinator User',
            'email' => 'coordinator@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->create(['user_id' => $coord->id]);

        $instructorIds = [];
        for ($i = 1; $i <= 24; $i++) {
            $user = User::query()->create([
                'role_id' => $role('instructor')->id,
                'department_id' => $dept->id,
                'full_name' => "Dr. Instructor {$i}",
                'email' => "instructor{$i}@optitime.local",
                'password_hash' => Hash::make('password'),
                'is_active' => true,
            ]);
            UserSetting::query()->create(['user_id' => $user->id]);
            $instructor = Instructor::query()->create([
                'user_id' => $user->id,
                'specialization_id' => $spec->id,
                'min_work_hours_per_week' => 0,
                'max_work_hours_per_week' => 22 + ($i % 4) * 2,
            ]);
            $instructorIds[] = $instructor->id;
        }

        for ($i = 1; $i <= 400; $i++) {
            $user = User::query()->create([
                'role_id' => $role('student')->id,
                'department_id' => $dept->id,
                'full_name' => "Student {$i}",
                'email' => "student{$i}@optitime.local",
                'password_hash' => Hash::make('password'),
                'is_active' => true,
            ]);
            UserSetting::query()->create(['user_id' => $user->id]);
            Student::query()->create([
                'user_id' => $user->id,
                'university_number' => 'STU-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'completed_hours' => max(0, ($i % 140) - 10),
                'year_level' => (($i - 1) % 4) + 1,
                'study_status' => 'regular',
            ]);
        }

        foreach (['management', 'exams'] as $rc) {
            $u = User::query()->create([
                'role_id' => $role($rc)->id,
                'department_id' => null,
                'full_name' => ucfirst($rc).' User',
                'email' => $rc.'@optitime.local',
                'password_hash' => Hash::make('password'),
                'is_active' => true,
            ]);
            UserSetting::query()->create(['user_id' => $u->id]);
        }

        foreach ($sections as $index => $section) {
            $firstInstructor = $instructorIds[$index % count($instructorIds)];
            CourseSectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => $firstInstructor]);
            if ($index % 3 === 0) {
                $secondInstructor = $instructorIds[($index + 7) % count($instructorIds)];
                CourseSectionInstructor::query()->create(['section_id' => $section->id, 'instructor_id' => $secondInstructor]);
            }
        }

        $this->command?->info('Seeded: 400 students, 24 instructors, 22 courses, 44 sections, 32 rooms. Password: password');
    }
}
