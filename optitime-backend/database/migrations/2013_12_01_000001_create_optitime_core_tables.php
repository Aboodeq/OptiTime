<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 120);
            $table->string('name_en', 120);
            $table->string('sidebar_color', 20)->default('#4361ee');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100)->unique();
            $table->string('module', 80);
            $table->string('action', 80);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['module', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamp('granted_at')->useCurrent();
            $table->uuid('granted_by')->nullable();
            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('faculties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->integer('graduation_hours')->default(0);
            $table->integer('studying_level')->default(4);
            $table->string('color', 20)->default('#4361ee');
            $table->text('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('faculty_id')->constrained('faculties')->cascadeOnDelete();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->text('icon_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('role_id')->constrained('roles');
            $table->foreignUuid('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('full_name', 150);
            $table->string('email', 190)->unique();
            $table->text('avatar_url')->nullable();
            $table->string('password_hash', 255);
            $table->timestamp('password_updated_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_settings', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->boolean('email_schedule_updates')->default(true);
            $table->boolean('email_reminders')->default(true);
            $table->boolean('push_announcements')->default(true);
            $table->boolean('push_system_alerts')->default(true);
            $table->boolean('weekly_digest')->default(false);
            $table->timestamps();
        });

        Schema::create('system_backups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('initiated_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('file_name', 255);
            $table->text('storage_path')->nullable();
            $table->string('backup_scope', 50)->default('user_settings');
            $table->string('status', 30)->default('completed');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id', 80)->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 190);
            $table->text('message');
            $table->string('type', 50);
            $table->string('priority', 20)->default('low');
            $table->boolean('is_read')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 20)->default('info');
            $table->string('module', 80);
            $table->string('action', 120);
            $table->text('description')->nullable();
            $table->string('target_table', 80)->nullable();
            $table->uuid('target_id')->nullable();
            $table->string('request_id', 120)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('metadata')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('specializations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('specialization_id')->constrained('specializations')->cascadeOnDelete();
            $table->integer('min_work_hours_per_week')->default(0);
            $table->integer('max_work_hours_per_week')->default(0);
            $table->timestamps();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('code', 50);
            $table->string('name_ar', 190);
            $table->string('name_en', 190);
            $table->integer('required_hours')->default(1);
            $table->integer('room_consumed_hours')->nullable();
            $table->integer('lab_consumed_hours')->nullable();
            $table->boolean('has_lab_component')->default(false);
            $table->timestamps();
            $table->unique(['department_id', 'code']);
        });

        Schema::create('course_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('section_name', 120);
            $table->string('section_type', 20)->default('room');
            $table->integer('capacity')->default(1);
            $table->timestamps();
            $table->unique(['course_id', 'section_name']);
        });

        Schema::create('course_section_instructors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('section_id')->constrained('course_sections')->cascadeOnDelete();
            $table->foreignUuid('instructor_id')->constrained('instructors')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['section_id', 'instructor_id']);
        });

        Schema::create('course_constraints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->unique()->constrained('courses')->cascadeOnDelete();
            $table->integer('min_student_year_level')->nullable();
            $table->integer('max_student_year_level')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('prerequisite_course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['course_id', 'prerequisite_course_id']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('academic_year', 20);
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['academic_year', 'code']);
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained('courses')->cascadeOnDelete();
            $table->foreignUuid('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['course_id', 'semester_id']);
        });

        Schema::create('students', function (Blueprint $table) {
            $table->foreignUuid('user_id')->primary()->constrained('users')->cascadeOnDelete();
            $table->string('university_number', 40)->unique();
            $table->integer('completed_hours')->default(0);
            $table->integer('year_level')->default(1);
            $table->string('study_status', 30)->default('regular');
            $table->timestamps();
        });

        Schema::table('role_permissions', function (Blueprint $table) {
            $table->foreign('granted_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('role_permissions', function (Blueprint $table) {
            $table->dropForeign(['granted_by']);
        });

        Schema::dropIfExists('students');
        Schema::dropIfExists('course_offerings');
        Schema::dropIfExists('semesters');
        Schema::dropIfExists('course_prerequisites');
        Schema::dropIfExists('course_constraints');
        Schema::dropIfExists('course_section_instructors');
        Schema::dropIfExists('course_sections');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('instructors');
        Schema::dropIfExists('specializations');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('user_settings');
        Schema::dropIfExists('users');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('faculties');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
