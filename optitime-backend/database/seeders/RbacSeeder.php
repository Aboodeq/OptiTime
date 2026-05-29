<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            'dashboard.view',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
            'users.view', 'users.create', 'users.update', 'users.delete',
            'faculties.view', 'faculties.create', 'faculties.update', 'faculties.delete',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete',
            'rooms.view', 'rooms.create', 'rooms.update', 'rooms.delete',
            'courses.view', 'courses.create', 'courses.update', 'courses.delete',
            'sections.view', 'sections.create', 'sections.update', 'sections.delete',
            'specializations.view', 'specializations.create', 'specializations.update', 'specializations.delete',
            'semesters.view', 'semesters.create', 'semesters.update', 'semesters.delete',
            'schedule_settings.view', 'schedule_settings.create', 'schedule_settings.update', 'schedule_settings.delete',
            'audit_logs.view',
            'system_backups.create',
            'resources.view', 'resources.create', 'resources.update', 'resources.delete',
            'coordinator_rooms.view', 'coordinator_rooms.create', 'coordinator_rooms.update', 'coordinator_rooms.delete',
            'coordinator_courses.view', 'coordinator_courses.create', 'coordinator_courses.update', 'coordinator_courses.delete',
            'coordinator_sections.view', 'coordinator_sections.create', 'coordinator_sections.update', 'coordinator_sections.delete',
            'lecture_requests.view', 'lecture_requests.update',
            'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete', 'schedules.publish',
            'schedule.generate',
            'instructor_availability.view', 'instructor_availability.create', 'instructor_availability.update', 'instructor_availability.delete',
            'instructor.schedule.view', 'instructor.schedule.export',
            'instructor.requests.view', 'instructor.requests.create', 'instructor.requests.update', 'instructor.requests.delete',
            'student.schedule.view', 'student.schedule.export',
            'student.grades.view', 'student.grades.export',
            'reports.classroom_occupancy', 'reports.study_hours', 'reports.lab_utilization',
            'reports.peak_periods', 'reports.resource_utilization', 'reports.compliance',
            'exam_sessions.view', 'exam_sessions.update',
            'exam_grades.view', 'exam_grades.update', 'exam_grades.export',
            'profile.view', 'profile.update',
            'notifications.view', 'notifications.update',
        ];

        foreach ($codes as $code) {
            $parts = explode('.', $code, 2);
            $module = $parts[0];
            $action = $parts[1] ?? 'view';
            Permission::query()->updateOrCreate(
                ['code' => $code],
                [
                    'module' => $module,
                    'action' => $action,
                    'description' => $code,
                    'is_system' => true,
                ]
            );
        }

        $byCode = fn (array $c) => Permission::query()->whereIn('code', $c)->pluck('id')->all();

        $roleDefs = [
            'admin' => array_values(Permission::query()->pluck('id')->all()),
            'coordinator' => $byCode([
                'dashboard.view',
                'resources.view', 'resources.create', 'resources.update', 'resources.delete',
                'coordinator_rooms.view', 'coordinator_rooms.create', 'coordinator_rooms.update', 'coordinator_rooms.delete',
                'coordinator_courses.view', 'coordinator_courses.create', 'coordinator_courses.update', 'coordinator_courses.delete',
                'coordinator_sections.view', 'coordinator_sections.create', 'coordinator_sections.update', 'coordinator_sections.delete',
                'lecture_requests.view', 'lecture_requests.update',
                'schedules.view', 'schedules.create', 'schedules.update', 'schedules.delete', 'schedules.publish',
                'schedule.generate',
                'semesters.view',
                'profile.view', 'profile.update',
                'notifications.view', 'notifications.update',
            ]),
            'instructor' => $byCode([
                'dashboard.view',
                'instructor_availability.view', 'instructor_availability.create', 'instructor_availability.update', 'instructor_availability.delete',
                'instructor.schedule.view', 'instructor.schedule.export',
                'instructor.requests.view', 'instructor.requests.create', 'instructor.requests.update', 'instructor.requests.delete',
                'profile.view', 'profile.update',
                'notifications.view', 'notifications.update',
            ]),
            'student' => $byCode([
                'dashboard.view',
                'student.schedule.view', 'student.schedule.export',
                'student.grades.view', 'student.grades.export',
                'profile.view', 'profile.update',
                'notifications.view', 'notifications.update',
            ]),
            'management' => $byCode([
                'dashboard.view',
                'courses.view',
                'semesters.view',
                'reports.classroom_occupancy', 'reports.study_hours', 'reports.lab_utilization',
                'reports.peak_periods', 'reports.resource_utilization', 'reports.compliance',
                'profile.view', 'profile.update',
                'notifications.view', 'notifications.update',
            ]),
            'exams' => $byCode([
                'dashboard.view',
                'exam_sessions.view', 'exam_sessions.update',
                'exam_grades.view', 'exam_grades.update', 'exam_grades.export',
                'profile.view', 'profile.update',
                'notifications.view', 'notifications.update',
            ]),
        ];

        foreach ($roleDefs as $code => $permIds) {
            $role = Role::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name_ar' => $code,
                    'name_en' => ucfirst($code),
                    'sidebar_color' => '#4361ee',
                    'is_system' => true,
                    'is_active' => true,
                ]
            );
            $role->permissions()->sync($permIds);
        }
    }
}
