<?php

use App\Http\Controllers\Api\Admin\AdminAuditController;
use App\Http\Controllers\Api\Admin\AdminCourseOfferingController;
use App\Http\Controllers\Api\Admin\AdminEntityController;
use App\Http\Controllers\Api\Admin\AdminInstructorController;
use App\Http\Controllers\Api\Admin\AdminRoleController;
use App\Http\Controllers\Api\Admin\AdminScheduleSettingCurrentController;
use App\Http\Controllers\Api\Admin\AdminSectionInstructorController;
use App\Http\Controllers\Api\Admin\AdminStudentController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Coordinator\CoordinatorCourseSemesterController;
use App\Http\Controllers\Api\Coordinator\CoordinatorEntityController;
use App\Http\Controllers\Api\Coordinator\CoordinatorLectureRequestController;
use App\Http\Controllers\Api\Coordinator\CoordinatorLookupController;
use App\Http\Controllers\Api\Coordinator\CoordinatorScheduleBoardContextController;
use App\Http\Controllers\Api\Coordinator\CoordinatorScheduleController;
use App\Http\Controllers\Api\Coordinator\CoordinatorSectionInstructorController;
use App\Http\Controllers\Api\Exams\ExamGradeController;
use App\Http\Controllers\Api\Exams\ExamSessionController;
use App\Http\Controllers\Api\Instructor\InstructorAvailabilityController;
use App\Http\Controllers\Api\Instructor\InstructorAvailabilityGridContextController;
use App\Http\Controllers\Api\Instructor\InstructorLectureRequestController;
use App\Http\Controllers\Api\Instructor\InstructorPdfController;
use App\Http\Controllers\Api\Instructor\InstructorScheduleController;
use App\Http\Controllers\Api\Management\ManagementReportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\Student\StudentGradeController;
use App\Http\Controllers\Api\Student\StudentPdfController;
use App\Http\Controllers\Api\Student\StudentScheduleController;
use App\Http\Controllers\Api\UserSettingController;
use App\Http\Controllers\ScheduleGenerationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register/request-otp', [AuthController::class, 'requestRegisterOtp']);
Route::post('/auth/register/verify-otp', [AuthController::class, 'verifyRegisterOtp']);
Route::post('/auth/forgot-password/request-otp', [AuthController::class, 'requestForgotPasswordOtp']);
Route::post('/auth/forgot-password/reset', [AuthController::class, 'resetPasswordWithOtp']);

Route::middleware('auth:sanctum')->group(function () {
    Route::middleware(['schedule.access'])->post('/schedule/generate', [ScheduleGenerationController::class, 'generate']);
    Route::middleware(['schedule.access'])->get('/schedule/generate/{id}', [ScheduleGenerationController::class, 'show']);

    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

    Route::middleware('permission:profile.view')->get('/profile', [ProfileController::class, 'show']);
    Route::middleware('permission:profile.update')->put('/profile', [ProfileController::class, 'update']);

    Route::middleware('permission:profile.view')->get('/profile/settings', [UserSettingController::class, 'show']);
    Route::middleware('permission:profile.update')->put('/profile/settings', [UserSettingController::class, 'update']);

    Route::middleware('permission:notifications.view')->get('/notifications', [NotificationController::class, 'index']);
    Route::middleware('permission:notifications.update')->post('/notifications/{id}/read', [NotificationController::class, 'markRead']);

    Route::prefix('admin')->group(function () {
        Route::middleware('permission:users.view')->get('/users', [AdminUserController::class, 'index']);
        Route::middleware('permission:users.create')->post('/users', [AdminUserController::class, 'store']);
        Route::middleware('permission:users.view')->get('/users/{id}', [AdminUserController::class, 'show']);
        Route::middleware('permission:users.update')->put('/users/{id}', [AdminUserController::class, 'update']);
        Route::middleware('permission:users.delete')->delete('/users/{id}', [AdminUserController::class, 'destroy']);

        Route::middleware('permission:users.view')->get('/instructors', [AdminInstructorController::class, 'index']);
        Route::middleware('permission:users.create')->post('/instructors', [AdminInstructorController::class, 'store']);
        Route::middleware('permission:users.view')->get('/instructors/{id}', [AdminInstructorController::class, 'show']);
        Route::middleware('permission:users.update')->put('/instructors/{id}', [AdminInstructorController::class, 'update']);
        Route::middleware('permission:users.delete')->delete('/instructors/{id}', [AdminInstructorController::class, 'destroy']);

        Route::middleware('permission:users.view')->get('/students', [AdminStudentController::class, 'index']);
        Route::middleware('permission:users.create')->post('/students', [AdminStudentController::class, 'store']);
        Route::middleware('permission:users.view')->get('/students/{id}', [AdminStudentController::class, 'show']);
        Route::middleware('permission:users.update')->put('/students/{id}', [AdminStudentController::class, 'update']);
        Route::middleware('permission:users.delete')->delete('/students/{id}', [AdminStudentController::class, 'destroy']);

        Route::middleware('permission:roles.view')->get('/roles', [AdminRoleController::class, 'index']);
        Route::middleware('permission:roles.create')->post('/roles', [AdminRoleController::class, 'store']);
        Route::middleware('permission:roles.view')->get('/roles/{id}', [AdminRoleController::class, 'show']);
        Route::middleware('permission:roles.update')->put('/roles/{id}', [AdminRoleController::class, 'update']);
        Route::middleware('permission:roles.delete')->delete('/roles/{id}', [AdminRoleController::class, 'destroy']);
        Route::middleware('permission:roles.view')->get('/permissions', [AdminRoleController::class, 'permissions']);

        Route::middleware('permission:schedule_settings.view')->get('/schedule-settings/current', [AdminScheduleSettingCurrentController::class, 'show']);
        Route::middleware('permission:schedule_settings.update')->put('/schedule-settings/current', [AdminScheduleSettingCurrentController::class, 'update']);

        foreach (['faculties', 'departments', 'specializations', 'semesters', 'rooms', 'resources', 'courses', 'sections', 'schedule-settings'] as $res) {
            Route::middleware('permission:'.str_replace('-', '_', $res).'.view')->get('/'.$res, [AdminEntityController::class, 'index'])->defaults('resource', $res);
            Route::middleware('permission:'.str_replace('-', '_', $res).'.create')->post('/'.$res, [AdminEntityController::class, 'store'])->defaults('resource', $res);
            Route::middleware('permission:'.str_replace('-', '_', $res).'.view')->get('/'.$res.'/{id}', [AdminEntityController::class, 'show'])->defaults('resource', $res);
            Route::middleware('permission:'.str_replace('-', '_', $res).'.update')->put('/'.$res.'/{id}', [AdminEntityController::class, 'update'])->defaults('resource', $res);
            Route::middleware('permission:'.str_replace('-', '_', $res).'.delete')->delete('/'.$res.'/{id}', [AdminEntityController::class, 'destroy'])->defaults('resource', $res);
        }

        Route::middleware('permission:sections.update')->post('/sections/{section}/instructors', [AdminSectionInstructorController::class, 'sync']);
        Route::middleware('permission:courses.view')->get('/course-offerings', [AdminCourseOfferingController::class, 'index']);
        Route::middleware('permission:courses.update')->post('/course-semester', [CoordinatorCourseSemesterController::class, 'sync']);

        Route::middleware('permission:audit_logs.view')->get('/audit-logs', [AdminAuditController::class, 'index']);
    });

    Route::prefix('coordinator')->group(function () {
        Route::middleware('permission:coordinator_rooms.view')->get('/lookups/buildings', [CoordinatorLookupController::class, 'buildings']);
        Route::middleware('permission:coordinator_rooms.view')->get('/lookups/room-types', [CoordinatorLookupController::class, 'roomTypes']);
        Route::middleware('permission:coordinator_courses.view')->get('/lookups/course-types', [CoordinatorLookupController::class, 'courseTypes']);

        foreach (['resources', 'rooms', 'courses', 'sections'] as $res) {
            $perm = match ($res) {
                'rooms' => 'coordinator_rooms',
                'courses' => 'coordinator_courses',
                'sections' => 'coordinator_sections',
                default => 'resources',
            };
            Route::middleware('permission:'.$perm.'.view')->get('/'.$res, [CoordinatorEntityController::class, 'index'])->defaults('resource', $res);
            Route::middleware('permission:'.$perm.'.create')->post('/'.$res, [CoordinatorEntityController::class, 'store'])->defaults('resource', $res);
            Route::middleware('permission:'.$perm.'.view')->get('/'.$res.'/{id}', [CoordinatorEntityController::class, 'show'])->defaults('resource', $res);
            Route::middleware('permission:'.$perm.'.update')->put('/'.$res.'/{id}', [CoordinatorEntityController::class, 'update'])->defaults('resource', $res);
            Route::middleware('permission:'.$perm.'.delete')->delete('/'.$res.'/{id}', [CoordinatorEntityController::class, 'destroy'])->defaults('resource', $res);
        }

        Route::middleware('permission:coordinator_sections.update')->post('/sections/{section}/instructors', [CoordinatorSectionInstructorController::class, 'sync']);

        Route::middleware('permission:coordinator_courses.update')->post('/course-semester', [CoordinatorCourseSemesterController::class, 'sync']);

        Route::middleware('permission:lecture_requests.view')->get('/lecture-requests', [CoordinatorLectureRequestController::class, 'index']);
        Route::middleware('permission:lecture_requests.update')->put('/lecture-requests/{id}', [CoordinatorLectureRequestController::class, 'update']);

        Route::middleware('permission:schedules.view')->get('/schedule-board-context', [CoordinatorScheduleBoardContextController::class, 'show']);
        Route::middleware('permission:schedules.view')->get('/schedules', [CoordinatorScheduleController::class, 'index']);
        Route::middleware('permission:schedules.create')->post('/schedules', [CoordinatorScheduleController::class, 'store']);
        Route::middleware('permission:schedules.update')->put('/schedules/{id}/sessions', [CoordinatorScheduleController::class, 'syncSessions']);
        Route::middleware('permission:schedules.view')->get('/schedules/{id}', [CoordinatorScheduleController::class, 'show']);
        Route::middleware('permission:schedules.update')->put('/schedules/{id}', [CoordinatorScheduleController::class, 'update']);
        Route::middleware('permission:schedules.delete')->delete('/schedules/{id}', [CoordinatorScheduleController::class, 'destroy']);
        Route::middleware('permission:schedules.publish')->post('/schedules/publish-from-generation', [CoordinatorScheduleController::class, 'publishFromGeneration']);
    });

    Route::prefix('instructor')->group(function () {
        Route::middleware('permission:instructor_availability.view')->get('/availability-grid-context', [InstructorAvailabilityGridContextController::class, 'show']);
        Route::middleware('permission:instructor_availability.view')->get('/availabilities', [InstructorAvailabilityController::class, 'index']);
        Route::middleware('permission:instructor_availability.create')->post('/availabilities', [InstructorAvailabilityController::class, 'store']);
        Route::middleware('permission:instructor_availability.update')->put('/availabilities/{availability}', [InstructorAvailabilityController::class, 'update']);
        Route::middleware('permission:instructor_availability.delete')->delete('/availabilities/{availability}', [InstructorAvailabilityController::class, 'destroy']);

        Route::middleware('permission:instructor.schedule.view')->get('/weekly-schedule', [InstructorScheduleController::class, 'weekly']);
        Route::middleware('permission:instructor.schedule.export')->get('/weekly-schedule/pdf', [InstructorPdfController::class, 'weeklySchedule']);

        Route::middleware('permission:instructor.requests.view')->get('/lecture-requests', [InstructorLectureRequestController::class, 'index']);
        Route::middleware('permission:instructor.requests.create')->post('/lecture-requests', [InstructorLectureRequestController::class, 'store']);
    });

    Route::prefix('student')->group(function () {
        Route::middleware('permission:student.schedule.view')->get('/weekly-schedule', [StudentScheduleController::class, 'weekly']);
        Route::middleware('permission:student.schedule.export')->get('/weekly-schedule/pdf', [StudentPdfController::class, 'weeklySchedule']);
        Route::middleware('permission:student.grades.view')->get('/grades', [StudentGradeController::class, 'bySemester']);
        Route::middleware('permission:student.grades.export')->get('/grades/pdf', [StudentPdfController::class, 'grades']);
    });

    Route::prefix('management')->group(function () {
        Route::middleware('permission:reports.classroom_occupancy')->get('/reports/classroom-occupancy', [ManagementReportController::class, 'classroomOccupancy']);
        Route::middleware('permission:reports.lab_utilization')->get('/reports/lab-utilization', [ManagementReportController::class, 'labUtilization']);
        Route::middleware('permission:reports.study_hours')->get('/reports/study-hours-distribution', [ManagementReportController::class, 'studyHoursDistribution']);
        Route::middleware('permission:reports.peak_periods')->get('/reports/peak-periods', [ManagementReportController::class, 'peakPeriods']);
        Route::middleware('permission:reports.resource_utilization')->get('/reports/resource-utilization', [ManagementReportController::class, 'resourceUtilization']);
        Route::middleware('permission:reports.compliance')->get('/reports/compliance-restrictions', [ManagementReportController::class, 'complianceRestrictions']);
    });

    Route::prefix('exams')->group(function () {
        Route::middleware('permission:exam_sessions.view')->get('/sessions', [ExamSessionController::class, 'index']);
        Route::middleware('permission:exam_sessions.update')->post('/sessions/mark-done', [ExamSessionController::class, 'markDone']);
        Route::middleware('permission:exam_grades.update')->post('/grades', [ExamGradeController::class, 'storeOrUpdate']);
        Route::middleware('permission:exam_grades.export')->get('/grades/session-pdf', [ExamGradeController::class, 'exportSessionPdf']);
    });
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load(['role.permissions', 'instructor']);
});
