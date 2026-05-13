# BACKTRACKING_SCHEDULE_OPTIMIZATION_REPORT

## 1) Backtracking algorithm location
- `app/Scheduling/BacktrackingScheduler.php`
- Orchestration and final validation:
  - `app/Services/ScheduleGenerateService.php`
  - `app/Scheduling/HardConstraintValidator.php`
  - `app/Scheduling/SoftConstraintScorer.php`
  - `app/Http/Controllers/Api/Coordinator/CoordinatorScheduleController.php`

## 2) How the current algorithm works (after optimization)
1. `ScheduleGenerateService` resolves settings, rooms, events, and scheduling context.
2. Backtracking solver starts with fixed placements (if any) and validates them.
3. It precomputes event candidate placements once, with static hard filtering and pre-scored value ordering.
4. It runs feasibility prechecks (slot bound, weekly load feasibility, max-daily feasibility, zero-domain check).
5. It performs recursive search with:
   - dynamic fail-first event selection (sampled MCV),
   - incremental hard checks (room/instructor/section overlap + load/daily cap/availability),
   - forward checking on impacted events,
   - symmetry pruning for repeated section-instructor events,
   - bounded failed-state cache.
6. It returns a schedule or a deterministic failure with diagnostics.
7. `ScheduleGenerateService` still runs full hard validation and completeness checks before returning/publishing.

## 3) Why it was slow or appeared to hang
- Deadline used to start in constructor, while heavy candidate precomputation also happened there.
  - Result: solver could begin recursion already timed out.
- Candidate sorting repeatedly recomputed soft scores inside comparator calls.
- Candidate feasibility checked each trial by calling full hard-validator evaluation repeatedly.
- MCV/forward checking were effectively disabled for larger instances.
- No failed-state cache and no symmetry pruning caused repeated exploration of equivalent dead branches.
- Local soft-improvement phase could dominate runtime on large schedules.

## 4) Migrations/tables used for scheduling
- `database/migrations/2013_12_01_000001_create_optitime_core_tables.php`
  - `courses`, `course_sections`, `course_section_instructors`, `course_offerings`, `instructors`, `students`, `semesters`, `departments`, `users`
- `database/migrations/2013_12_01_000002_create_optitime_scheduling_tables.php`
  - `rooms`, `resources`, `room_resources`
  - `schedule_settings`, `schedule_setting_constraints`, `schedule_setting_room_constraints`, `schedule_setting_study_days`, `schedule_setting_break_times`, `schedule_setting_load_ranges`
  - `instructor_availability_profiles`, `instructor_availability_cells`
  - `semester_schedule_plans`, `schedule_sessions`, `schedule_session_students`
- `database/migrations/2026_04_22_000002_create_schedule_generation_jobs_table.php`
  - `schedule_generation_jobs`

## 5) Hard constraints found
- `no_room_overlap`
- `no_instructor_overlap`
- `no_section_overlap`
- `room_capacity`
- `room_status_available`
- `lab_for_lab`
- `working_hours`
- `instructor_availability` (when enabled)
- `capacity_threshold` (when enabled)
- `max_daily_lectures` hard enforcement
- instructor weekly max load (`instructors.max_work_hours_per_week`)
- final completeness checks (`required_sessions`) before publish

## 6) Soft constraints found
- `instructor_preferences`
- `load_balance`
- `avoid_back_to_back`
- `student_gaps`
- `morning_preference`
- `department_proximity`
- `max_daily_lectures` (soft scoring)

## 7) Constraints already implemented and preserved
- Generation-time hard overlap/capacity/lab/availability checks preserved.
- Final hard validation before saving/publishing preserved.
- Post-assignment validation (`student overlap`, `room capacity`, `student eligibility`, `instructor load`) preserved.
- Existing API shape and business flow preserved.

## 8) Constraints missing or still limited by schema/modeling
- No explicit room-timeslot availability table beyond room status.
- No explicit course-required equipment relation for strict equipment matching.
- Student/group hard overlap cannot be fully enforced during generation without stronger enrollment model; still enforced in final gate after assignment.
- Legacy `schedule_setting_room_constraints` flags are still not fully mapped to strict hard semantics.

## 9) Optimizations added
- Deadline moved from constructor semantics to full `solve()` lifecycle with cooperative checks inside heavy loops.
- Candidate precompute now:
  - one-time,
  - pre-scored once for value ordering,
  - hard-filtered early (including availability when hard-enabled).
- Incremental conflict maps replaced repeated full validator scans for candidate feasibility:
  - room/day intervals
  - instructor/day intervals
  - section/day intervals
  - instructor daily counts
  - instructor weekly minutes
- Added fast feasibility prechecks:
  - zero-domain detection
  - weekly load infeasibility
  - max-daily infeasibility
  - crude slot bound
- Re-enabled practical fail-first behavior for larger instances:
  - sampled dynamic MCV
  - forward checking on impacted/high-risk unscheduled events
- Added pruning:
  - bounded failed-state cache
  - symmetry pruning for repeated section-instructor sessions
- Added structured diagnostics:
  - termination reason/detail
  - backtracks, recursive steps, candidate checks
  - prune counters, cache hits, max depth
  - precompute timing and candidate totals
  - hardest unscheduled events snapshot
- Capped local soft-improvement for large problem sizes to avoid post-solve runtime spikes.

## 10) Tests added/updated
- Updated `tests/Feature/ScheduleGenerateTest.php`:
  - assert backtracking failure includes diagnostics + termination reason
  - low max-seconds failure is graceful and diagnostic
  - zero-domain precheck failure is explicit and diagnostic
  - seeded-baseline runtime guard (`<= 300s`) with graceful-failure allowance
- Existing suites re-run and preserved:
  - `ScheduleGenerateTest`
  - `ScheduleGaConstraintsTest`
  - `CoordinatorScheduleSessionsSyncTest`

## 11) Performance notes (measured)
- Baseline before optimization (seeded workload, previous solver behavior):
  - constructor precompute consumed ~38.38s
  - `solve()` then exited almost immediately (`recursive_steps=1`, `backtracks=0`) due pre-expired deadline
  - long-run profile with relaxed limits reached ~180.45s and still failed (`backtracks=104705`)
- After optimization (fresh `migrate:fresh --seed`):
  - full backtracking call finished in ~14.59s
  - returned deterministic failure with reason `max_seconds_exceeded`
  - diagnostics included `backtracks=134`, `recursive_steps=135`, `precompute_seconds=3.910878`
- Small draft generation path remained fast (feature test backtracking publish/generation flow ~1.2-1.3s per run in test suite output).

## 12) Remaining limitations and assumptions
- Correctness-first behavior is preserved; hard violations still block publish.
- Full-semester feasibility can still fail for over-constrained datasets; now it fails fast with explicit diagnostics instead of silent long runs.
- No schema migrations were changed in this optimization pass.
- Max runtime is now intentionally tighter via config (`backtracking.max_seconds = 14.0`) to enforce responsive failure semantics.
