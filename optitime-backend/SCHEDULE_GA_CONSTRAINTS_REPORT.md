# SCHEDULE_GA_CONSTRAINTS_REPORT

## 1) Genetic Algorithm Code Location
- `app/Scheduling/GeneticScheduler.php`
- `app/Scheduling/BacktrackingScheduler.php`
- `app/Scheduling/ConstraintEvaluator.php` (compatibility adapter)
- Hard/soft engines:
  - `app/Scheduling/HardConstraintValidator.php`
  - `app/Scheduling/SoftConstraintScorer.php`
- Orchestration and final validation:
  - `app/Services/ScheduleGenerateService.php`
  - `app/Http/Controllers/Api/Coordinator/CoordinatorScheduleController.php`
  - `app/Services/ScheduleSessionStudentAssignmentService.php`

## 2) Current Algorithm Flow Summary
1. Resolve schedule settings from DB (`settings_id`) or payload.
2. Validate prerequisite settings shape (required hard/soft keys, valid day/slot ranges).
3. Build legal grid cells from enabled study days + day range + slot/gap + breaks.
4. Build rooms list with status and type.
5. Build placement events:
- from `baseDraft.sessions` with strict DB relation checks, or
- from semester offerings/sections/instructors with required weekly expansion.
6. Build scheduling context (legal cells, instructor preference/unavailable windows, shared-student section pairs, instructor weekly limits).
7. Solve with GA/backtracking.
8. Score with hard/soft separation.
9. Return diagnostics (`hard_breakdown`, `validation_errors`, `soft_penalty`).
10. Before publish/update-to-published: run strict final hard validation. If it fails, reject.
11. After persistence attempt, run post-assignment validations in transaction and rollback on violations.

## 3) Scheduling Migrations / Tables Used
### Core schema
- `database/migrations/2013_12_01_000001_create_optitime_core_tables.php`
- `faculties`, `departments`, `users`, `instructors`, `courses`, `course_sections`, `course_section_instructors`, `course_constraints`, `course_prerequisites`, `semesters`, `course_offerings`, `students`

### Scheduling schema
- `database/migrations/2013_12_01_000002_create_optitime_scheduling_tables.php`
- `rooms`, `resources`, `room_resources`
- `schedule_settings`, `schedule_setting_constraints`, `schedule_setting_room_constraints`, `schedule_setting_study_days`, `schedule_setting_break_times`, `schedule_setting_load_ranges`
- `instructor_availability_profiles`, `instructor_availability_cells`
- `semester_schedule_plans`, `schedule_sessions`, `schedule_session_students`

### Generation queue
- `database/migrations/2026_04_22_000002_create_schedule_generation_jobs_table.php`
- `schedule_generation_jobs`

### Additional source audited
- `Code/optitime.sql` backup (real inserted constraint keys and enabled flags)

## 4) Hard Constraints Found and Enforcement Status
### Enforced now
- `no_room_overlap`
- `no_instructor_overlap`
- `no_section_overlap`
- `room_capacity` (section enrollment/capacity vs room capacity)
- `room_status_available` (`rooms.status = available`)
- `lab_for_lab` (lab section requires lab room)
- `working_hours` (must map to legal grid cell)
- `instructor_availability` (when hard-enabled)
- `capacity_threshold` (when hard-enabled)
- `max_daily_lectures` hard enforcement (explicit hard key, or soft-key backward-compat fallback)
- instructor weekly max load from `instructors.max_work_hours_per_week`
- final required session completeness (`required_sessions`) before publish
- draft/payload relation integrity:
  - section-instructor exists
  - offering exists and belongs to semester
  - section course matches offering course
  - instructor mapping consistency

### Publish/update final gates
- pre-publish hard validation blocks invalid plans
- transactional save + assignment + rollback on post-assignment violations
- post-assignment checks:
  - student overlap
  - room over-capacity by actual assigned students
  - student year-level eligibility by `course_constraints`
  - instructor weekly load limits

## 5) Soft Constraints Found and Scoring Model
Settings-driven weighted soft scoring:
- `instructor_preferences`
- `load_balance`
- `avoid_back_to_back`
- `student_gaps`
- `morning_preference`
- `department_proximity`
- `max_daily_lectures` (soft penalty)
- capacity-threshold penalty when threshold is not hard

## 6) Implemented-vs-Missing Matrix (Before -> After)
### Before
- Hard/soft logic was partly scattered.
- Final publish validation did not consistently block all invalid results.
- Max daily lectures not reliably hard-enforced.
- Post-assignment capacity and workload checks were incomplete.

### After
- Centralized hard validator and soft scorer.
- GA mutation/repair now targets violating events.
- Final publish/update gates enforce hard constraints before saving.
- Transactional post-assignment rollback with explicit diagnostics.
- Max daily lectures enforced as hard via explicit key or compatibility fallback.
- Instructor weekly load and room capacity checks expanded.

## 7) Exact Code Changes Made
### Updated
- `app/Scheduling/SchedulingContext.php`
- `app/Scheduling/ScheduleSettings.php`
- `app/Scheduling/HardConstraintValidator.php`
- `app/Services/ScheduleGenerateService.php`
- `app/Services/ScheduleSessionStudentAssignmentService.php`
- `app/Http/Controllers/Api/Coordinator/CoordinatorScheduleController.php`
- `tests/Feature/ScheduleGaConstraintsTest.php`
- `tests/Feature/CoordinatorScheduleSessionsSyncTest.php`

## 8) Tests Added/Updated and Results
### Added/expanded coverage
- hard validation checks for:
  - room overlap
  - instructor overlap
  - section overlap
  - room capacity
  - lab room type
  - instructor availability
  - room status
  - max daily lectures hard enforcement
  - required session completeness
- publish endpoint rejects invalid final schedule
- sync/update coordinator flows with stricter pre-publish behavior
- student assignment behavior: prerequisites + no time conflict

### Commands run
- `php artisan test --filter="CoordinatorScheduleSessionsSyncTest|ScheduleGaConstraintsTest|ScheduleGenerateTest"`
- `php artisan test`

### Result
- Targeted scheduling suites: pass (with PHP 8.5 deprecation notices from DB config constant)
- Full suite: one unrelated failure remains in `Tests\Feature\ExampleTest` (`MissingAppKeyException`)

## 9) Constraints Still Not Fully Respected (Current Schema Limits)
The following are present as flags or requested concepts but are not fully enforceable with current DB semantics/code data:
- `schedule_setting_room_constraints` legacy flags are not semantically mapped end-to-end:
  - `room_proximity`
  - `avoid_floor_scatter`
  - `equipment_match`
  - `match_type` (only lab-vs-non-lab is currently enforced through `lab_for_lab`)
  - `max_occupancy_threshold` (separate `capacity_threshold` is enforced)
- no explicit course-required equipment mapping table exists; room equipment matching cannot be validated precisely.
- no room timeslot availability table exists beyond room status.
- no student availability table exists.
- no explicit enrollment-by-section source table exists for generation-time hard student overlap; enforcement is section-level during generation and student-level after assignment.
- `schedule_setting_load_ranges` keys are loaded but not directly wired into hard validation semantics; instructor load is enforced from instructor profile min/max fields.

## 10) Remaining Assumptions / Limitations
- Manual draft sync now effectively requires legal grid-aligned sessions if final hard validation is triggered by that route.
- Full-semester completeness is required to publish (`required_sessions`), so partial drafts cannot be published.
- No migrations were changed in this hardening pass.
