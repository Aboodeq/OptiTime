<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar', 190);
            $table->string('name_en', 190);
            $table->string('type', 80);
            $table->integer('quantity')->default(0);
            $table->string('location_ar', 190)->nullable();
            $table->string('location_en', 190)->nullable();
            $table->string('status', 30)->default('available');
            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();
            $table->timestamps();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name_ar', 190);
            $table->string('name_en', 190);
            $table->string('type', 30)->default('class');
            $table->integer('capacity')->default(1);
            $table->string('location_ar', 190)->nullable();
            $table->string('location_en', 190)->nullable();
            $table->string('status', 30)->default('available');
            $table->text('notes_ar')->nullable();
            $table->text('notes_en')->nullable();
            $table->timestamps();
        });

        Schema::create('room_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('resource_id')->constrained('resources')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['room_id', 'resource_id']);
        });

        Schema::create('schedule_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->time('day_start');
            $table->time('day_end');
            $table->integer('slot_minutes')->default(60);
            $table->integer('gap_minutes')->default(15);
            $table->integer('max_daily_lectures')->default(4);
            $table->integer('capacity_threshold')->default(90);
            $table->timestamps();
        });

        Schema::create('schedule_setting_constraints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_setting_id')->constrained('schedule_settings')->cascadeOnDelete();
            $table->string('category', 30);
            $table->string('constraint_key', 100);
            $table->boolean('enabled')->default(true);
            $table->decimal('weight', 5, 2)->nullable()->default(1);
            $table->timestamps();
            $table->unique(['schedule_setting_id', 'constraint_key'], 'ssc_setting_key_uniq');
        });

        Schema::create('schedule_setting_room_constraints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_setting_id')->constrained('schedule_settings')->cascadeOnDelete();
            $table->string('constraint_key', 100);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['schedule_setting_id', 'constraint_key'], 'ssrc_setting_key_uniq');
        });

        Schema::create('schedule_setting_study_days', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_setting_id')->constrained('schedule_settings')->cascadeOnDelete();
            $table->string('day_value', 10);
            $table->boolean('enabled')->default(true);
            $table->unique(['schedule_setting_id', 'day_value'], 'sssd_setting_day_uniq');
        });

        Schema::create('schedule_setting_break_times', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_setting_id')->constrained('schedule_settings')->cascadeOnDelete();
            $table->string('break_key', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['schedule_setting_id', 'break_key'], 'ssbt_setting_break_uniq');
        });

        Schema::create('schedule_setting_load_ranges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_setting_id')->constrained('schedule_settings')->cascadeOnDelete();
            $table->string('load_key', 100);
            $table->integer('min_value')->default(0);
            $table->integer('max_value')->default(0);
            $table->timestamps();
            $table->unique(['schedule_setting_id', 'load_key'], 'sslr_setting_load_uniq');
        });

        Schema::create('instructor_availability_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('instructor_id')->unique()->constrained('instructors')->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('instructor_availability_cells', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('availability_profile_id')->constrained('instructor_availability_profiles')->cascadeOnDelete();
            $table->string('day_value', 10);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status', 20)->default('preferred');
            $table->timestamps();
            $table->unique(['availability_profile_id', 'day_value', 'start_time', 'end_time'], 'instructor_avail_cell_unique');
        });

        Schema::create('semester_schedule_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->string('status', 30)->default('draft');
            $table->string('selected_algorithm', 30)->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('room_id')->constrained('rooms')->cascadeOnDelete();
            $table->foreignUuid('schedule_plan_id')->constrained('semester_schedule_plans')->cascadeOnDelete();
            $table->foreignUuid('section_instructor_id')->constrained('course_section_instructors')->cascadeOnDelete();
            $table->foreignUuid('course_offering_id')->constrained('course_offerings')->cascadeOnDelete();
            $table->string('day_value', 10);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('conflict_state', 20)->default('none');
            $table->text('conflict_reasons')->nullable();
            $table->string('grades_entry_status', 20)->default('open');
            $table->timestamp('grades_marked_done_at')->nullable();
            $table->timestamps();
        });

        Schema::create('schedule_session_students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_session_id')->constrained('schedule_sessions')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('students', 'user_id')->cascadeOnDelete();
            $table->string('assignment_source', 20)->default('auto');
            $table->decimal('oral', 5, 2)->default(0);
            $table->decimal('lab', 5, 2)->default(0);
            $table->decimal('midterm', 5, 2)->default(0);
            $table->decimal('final', 5, 2)->default(0);
            $table->decimal('total', 5, 2)->default(0);
            $table->string('letter_grade', 5)->default('F');
            $table->timestamp('grade_entered_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['schedule_session_id', 'student_id']);
        });

        Schema::create('lecture_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('schedule_session_id')->constrained('schedule_sessions')->cascadeOnDelete();
            $table->foreignUuid('instructor_id')->constrained('instructors')->cascadeOnDelete();
            $table->string('request_type', 20);
            $table->date('requested_date');
            $table->text('note')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecture_requests');
        Schema::dropIfExists('schedule_session_students');
        Schema::dropIfExists('schedule_sessions');
        Schema::dropIfExists('semester_schedule_plans');
        Schema::dropIfExists('instructor_availability_cells');
        Schema::dropIfExists('instructor_availability_profiles');
        Schema::dropIfExists('schedule_setting_load_ranges');
        Schema::dropIfExists('schedule_setting_break_times');
        Schema::dropIfExists('schedule_setting_study_days');
        Schema::dropIfExists('schedule_setting_room_constraints');
        Schema::dropIfExists('schedule_setting_constraints');
        Schema::dropIfExists('schedule_settings');
        Schema::dropIfExists('room_resources');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('resources');
    }
};
