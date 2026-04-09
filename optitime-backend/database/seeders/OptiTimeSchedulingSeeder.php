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
        $faculty = Faculty::query()->create([
            'code' => 'informatics',
            'name_ar' => 'هندسة معلوماتية',
            'name_en' => 'Informatics',
            'graduation_hours' => 160,
            'studying_level' => 4,
            'color' => '#4361ee',
            'is_active' => true,
        ]);

        $dept = Department::query()->create([
            'faculty_id' => $faculty->id,
            'code' => 'software',
            'name_ar' => 'البرمجيات',
            'name_en' => 'Software',
            'is_active' => true,
        ]);

        $spec = Specialization::query()->create([
            'code' => 'se',
            'name_ar' => 'هندسة برمجيات',
            'name_en' => 'Software Engineering',
            'is_active' => true,
        ]);

        $semester = Semester::query()->create([
            'code' => '2026-s1',
            'name' => 'Spring 2026',
            'academic_year' => '2025-2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
        ]);

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
            'room_capacity' => true,
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

        Resource::query()->create([
            'name_ar' => 'جهاز عرض',
            'name_en' => 'Projector',
            'type' => 'projector',
            'quantity' => 4,
            'status' => 'available',
        ]);

        Room::query()->create([
            'name_ar' => 'قاعة أ',
            'name_en' => 'Hall A',
            'type' => 'hall',
            'capacity' => 120,
            'status' => 'available',
        ]);
        Room::query()->create([
            'name_ar' => 'قاعة ب',
            'name_en' => 'Hall B',
            'type' => 'class',
            'capacity' => 80,
            'status' => 'available',
        ]);
        Room::query()->create([
            'name_ar' => 'مختبر 1',
            'name_en' => 'Lab 1',
            'type' => 'lab',
            'capacity' => 40,
            'status' => 'available',
        ]);

        $c1 = Course::query()->create([
            'department_id' => $dept->id,
            'code' => 'CS101',
            'name_ar' => 'برمجة 1',
            'name_en' => 'Programming 1',
            'required_hours' => 3,
            'has_lab_component' => false,
        ]);
        $c2 = Course::query()->create([
            'department_id' => $dept->id,
            'code' => 'CS102L',
            'name_ar' => 'مختبر برمجة',
            'name_en' => 'Programming Lab',
            'required_hours' => 1,
            'has_lab_component' => true,
        ]);
        $c3 = Course::query()->create([
            'department_id' => $dept->id,
            'code' => 'MATH100',
            'name_ar' => 'تفاضل',
            'name_en' => 'Calculus',
            'required_hours' => 3,
            'has_lab_component' => false,
        ]);

        $sec1 = CourseSection::query()->create([
            'course_id' => $c1->id,
            'section_name' => 'Section A',
            'section_type' => 'room',
            'capacity' => 40,
        ]);
        $sec2 = CourseSection::query()->create([
            'course_id' => $c2->id,
            'section_name' => 'Lab A',
            'section_type' => 'lab',
            'capacity' => 25,
        ]);
        $sec3 = CourseSection::query()->create([
            'course_id' => $c3->id,
            'section_name' => 'Section A',
            'section_type' => 'room',
            'capacity' => 50,
        ]);

        foreach ([$c1, $c2, $c3] as $c) {
            CourseOffering::query()->create([
                'course_id' => $c->id,
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

        $uIns1 = User::query()->create([
            'role_id' => $role('instructor')->id,
            'department_id' => $dept->id,
            'full_name' => 'Dr. Ali',
            'email' => 'instructor1@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->create(['user_id' => $uIns1->id]);

        $uIns2 = User::query()->create([
            'role_id' => $role('instructor')->id,
            'department_id' => $dept->id,
            'full_name' => 'Dr. Sam',
            'email' => 'instructor2@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->create(['user_id' => $uIns2->id]);

        $uStu = User::query()->create([
            'role_id' => $role('student')->id,
            'department_id' => $dept->id,
            'full_name' => 'Student Demo',
            'email' => 'student@optitime.local',
            'password_hash' => Hash::make('password'),
            'is_active' => true,
        ]);
        UserSetting::query()->create(['user_id' => $uStu->id]);

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

        $ins1 = Instructor::query()->create([
            'user_id' => $uIns1->id,
            'specialization_id' => $spec->id,
            'min_work_hours_per_week' => 0,
            'max_work_hours_per_week' => 20,
        ]);
        $ins2 = Instructor::query()->create([
            'user_id' => $uIns2->id,
            'specialization_id' => $spec->id,
            'min_work_hours_per_week' => 0,
            'max_work_hours_per_week' => 20,
        ]);

        CourseSectionInstructor::query()->create(['section_id' => $sec1->id, 'instructor_id' => $ins1->id]);
        CourseSectionInstructor::query()->create(['section_id' => $sec2->id, 'instructor_id' => $ins1->id]);
        CourseSectionInstructor::query()->create(['section_id' => $sec3->id, 'instructor_id' => $ins2->id]);

        Student::query()->create([
            'user_id' => $uStu->id,
            'university_number' => 'STU-'.substr($uStu->id, 0, 8),
            'completed_hours' => 0,
            'year_level' => 2,
            'study_status' => 'regular',
        ]);

        $this->command?->info('Demo logins (password: password): admin@optitime.local, coordinator@optitime.local, student@optitime.local');
    }
}
