<?php

namespace Tests\Feature;

use App\Models\CourseOffering;
use App\Models\CourseSectionInstructor;
use App\Models\Room;
use App\Models\Semester;
use App\Models\SemesterSchedulePlan;
use App\Models\User;
use App\Scheduling\GridCell;
use App\Scheduling\Placement;
use App\Scheduling\PlacementEvent;
use App\Scheduling\RoomRef;
use App\Scheduling\ScheduleSettings;
use App\Scheduling\SchedulingContext;
use App\Scheduling\SoftConstraintScorer;
use App\Services\ScheduleGenerateService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ScheduleGaConstraintsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function actingAsCoordinator(): void
    {
        $user = User::query()->where('email', 'coordinator@optitime.local')->firstOrFail();
        Sanctum::actingAs($user);
    }

    private function semesterId(): string
    {
        return (string) Semester::query()->value('id');
    }

    private function offeringForCsi(CourseSectionInstructor $csi, string $semesterId): CourseOffering
    {
        return CourseOffering::query()
            ->where('course_id', $csi->section->course_id)
            ->where('semester_id', $semesterId)
            ->firstOrFail();
    }

    /**
     * @param  array<string, bool>  $hardOverrides
     * @param  array<string, array{enabled:bool,weight:float|int}>  $softOverrides
     * @return array<string, mixed>
     */
    private function scheduleSettings(array $hardOverrides = [], array $softOverrides = []): array
    {
        $hard = [
            'no_instructor_overlap' => true,
            'no_room_overlap' => true,
            'no_section_overlap' => true,
            'room_capacity' => true,
            'room_status_available' => true,
            'lab_for_lab' => true,
            'working_hours' => true,
            'instructor_availability' => true,
            'capacity_threshold' => false,
            'max_daily_lectures' => true,
        ];
        foreach ($hardOverrides as $key => $enabled) {
            $hard[$key] = (bool) $enabled;
        }

        $soft = [
            'instructor_preferences' => ['enabled' => false, 'weight' => 1],
            'load_balance' => ['enabled' => true, 'weight' => 1],
            'avoid_back_to_back' => ['enabled' => true, 'weight' => 1],
            'student_gaps' => ['enabled' => false, 'weight' => 1],
            'morning_preference' => ['enabled' => true, 'weight' => 1],
            'department_proximity' => ['enabled' => false, 'weight' => 1],
            'max_daily_lectures' => ['enabled' => true, 'weight' => 1],
        ];
        foreach ($softOverrides as $key => $value) {
            $soft[$key] = $value;
        }

        return [
            'study_days' => [
                'sunday' => true,
                'monday' => true,
                'tuesday' => true,
                'wednesday' => true,
                'thursday' => true,
                'friday' => false,
                'saturday' => false,
            ],
            'day_start' => '08:00',
            'day_end' => '18:00',
            'slot_minutes' => 50,
            'gap_minutes' => 10,
            'break_times' => [],
            'hard_constraints' => collect($hard)->map(fn (bool $enabled) => ['enabled' => $enabled])->all(),
            'soft_constraints' => $soft,
            'room_constraints' => [],
            'load_settings' => [],
            'capacity_threshold' => 0.9,
            'max_daily_lectures' => 5,
        ];
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function sessionRow(
        CourseSectionInstructor $csi,
        CourseOffering $offering,
        string $roomId,
        string $day,
        string $start,
        string $end,
        array $extra = []
    ): array {
        return array_merge([
            'course_offering_id' => $offering->id,
            'section_instructor_id' => $csi->id,
            'day' => $day,
            'start' => $start,
            'end' => $end,
            'room_id' => $roomId,
        ], $extra);
    }

    public function test_room_double_booking_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csiA = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $csiB = CourseSectionInstructor::query()->where('id', '!=', $csiA->id)->with('section')->firstOrFail();
        $offeringA = $this->offeringForCsi($csiA, $semesterId);
        $offeringB = $this->offeringForCsi($csiB, $semesterId);
        $roomId = (string) Room::query()->where('type', '!=', 'lab')->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csiA, $offeringA, $roomId, 'sun', '08:00', '08:50'),
                $this->sessionRow($csiB, $offeringB, $roomId, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('no_room_overlap', $result['hard_breakdown']);
    }

    public function test_instructor_double_booking_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $instructorId = (string) CourseSectionInstructor::query()
            ->select('instructor_id')
            ->groupBy('instructor_id')
            ->havingRaw('COUNT(*) >= 2')
            ->value('instructor_id');

        $csis = CourseSectionInstructor::query()
            ->where('instructor_id', $instructorId)
            ->with('section')
            ->take(2)
            ->get();

        $csiA = $csis[0];
        $csiB = $csis[1];
        $offeringA = $this->offeringForCsi($csiA, $semesterId);
        $offeringB = $this->offeringForCsi($csiB, $semesterId);

        $roomA = (string) Room::query()->where('type', '!=', 'lab')->orderBy('capacity')->value('id');
        $roomB = (string) Room::query()->where('type', '!=', 'lab')->where('id', '!=', $roomA)->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csiA, $offeringA, $roomA, 'sun', '08:00', '08:50'),
                $this->sessionRow($csiB, $offeringB, $roomB, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('no_instructor_overlap', $result['hard_breakdown']);
    }

    public function test_max_daily_lectures_is_hard_enforced_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $instructorId = (string) CourseSectionInstructor::query()
            ->select('instructor_id')
            ->groupBy('instructor_id')
            ->havingRaw('COUNT(*) >= 2')
            ->value('instructor_id');

        $csis = CourseSectionInstructor::query()
            ->where('instructor_id', $instructorId)
            ->with('section')
            ->take(2)
            ->get();

        $csiA = $csis[0];
        $csiB = $csis[1];
        $offeringA = $this->offeringForCsi($csiA, $semesterId);
        $offeringB = $this->offeringForCsi($csiB, $semesterId);

        $roomA = (string) Room::query()->where('type', '!=', 'lab')->orderBy('capacity')->value('id');
        $roomB = (string) Room::query()->where('type', '!=', 'lab')->where('id', '!=', $roomA)->value('id');

        $settings = $this->scheduleSettings();
        $settings['max_daily_lectures'] = 1;
        $settings['hard_constraints']['max_daily_lectures'] = ['enabled' => true];

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csiA, $offeringA, $roomA, 'sun', '08:00', '08:50'),
                $this->sessionRow($csiB, $offeringB, $roomB, 'sun', '10:00', '10:50'),
            ],
            $settings,
            null,
            null,
            false
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('max_daily_lectures', $result['hard_breakdown']);
    }

    public function test_section_overlap_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $sectionId = (string) CourseSectionInstructor::query()
            ->select('section_id')
            ->groupBy('section_id')
            ->havingRaw('COUNT(*) >= 2')
            ->value('section_id');

        $csis = CourseSectionInstructor::query()
            ->where('section_id', $sectionId)
            ->with('section')
            ->take(2)
            ->get();

        $csiA = $csis[0];
        $csiB = $csis[1];
        $offering = $this->offeringForCsi($csiA, $semesterId);

        $roomA = (string) Room::query()->where('type', '!=', 'lab')->orderBy('capacity')->value('id');
        $roomB = (string) Room::query()->where('type', '!=', 'lab')->where('id', '!=', $roomA)->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csiA, $offering, $roomA, 'sun', '08:00', '08:50'),
                $this->sessionRow($csiB, $offering, $roomB, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('no_section_overlap', $result['hard_breakdown']);
    }

    public function test_room_capacity_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $room = Room::query()->orderBy('capacity')->firstOrFail();

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow(
                    $csi,
                    $offering,
                    (string) $room->id,
                    'sun',
                    '08:00',
                    '08:50',
                    ['enrollment' => ((int) $room->capacity) + 10]
                ),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('room_capacity', $result['hard_breakdown']);
    }

    public function test_lab_section_requires_lab_room_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()
            ->whereHas('section', fn ($q) => $q->where('section_type', 'lab'))
            ->with('section')
            ->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $nonLabRoomId = (string) Room::query()->where('type', '!=', 'lab')->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csi, $offering, $nonLabRoomId, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('lab_for_lab', $result['hard_breakdown']);
    }

    public function test_instructor_availability_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $roomId = (string) Room::query()->where('type', '!=', 'lab')->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csi, $offering, $roomId, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            [
                [
                    'instructor_id' => $csi->instructor_id,
                    'day_of_week' => 'sun',
                    'start' => '10:00',
                    'end' => '11:00',
                    'status' => 'preferred',
                ],
            ]
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('instructor_availability', $result['hard_breakdown']);
    }

    public function test_unavailable_room_status_is_detected_in_final_validation(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $room = Room::query()->where('type', '!=', 'lab')->firstOrFail();
        $room->update(['status' => 'maintenance']);

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csi, $offering, (string) $room->id, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('room_status_available', $result['hard_breakdown']);
    }

    public function test_required_session_completeness_is_enforced_before_publish(): void
    {
        $service = app(ScheduleGenerateService::class);
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $roomId = (string) Room::query()->where('type', '!=', 'lab')->value('id');

        $result = $service->validateSessionsBeforePublish(
            $semesterId,
            [
                $this->sessionRow($csi, $offering, $roomId, 'sun', '08:00', '08:50'),
            ],
            $this->scheduleSettings(),
            null,
            null
        );

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('required_sessions', $result['hard_breakdown']);
    }

    public function test_publish_endpoint_rejects_invalid_final_schedule_and_does_not_save_published_plan(): void
    {
        $this->actingAsCoordinator();
        $semesterId = $this->semesterId();

        $csi = CourseSectionInstructor::query()->with('section')->firstOrFail();
        $offering = $this->offeringForCsi($csi, $semesterId);
        $roomId = (string) Room::query()->where('type', '!=', 'lab')->value('id');

        $before = SemesterSchedulePlan::query()->where('semester_id', $semesterId)->where('status', 'published')->count();

        $response = $this->postJson('/api/coordinator/schedules/publish-from-generation', [
            'algorithm' => 'backtracking',
            'semester_id' => $semesterId,
            'schedule_settings' => $this->scheduleSettings(),
            'baseDraft' => [
                'sessions' => [
                    $this->sessionRow($csi, $offering, $roomId, 'sun', '08:00', '08:50'),
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('reason', 'final hard-constraint validation failed before publish');
        $this->assertGreaterThan(0, (int) $response->json('meta.hard_breakdown.required_sessions'));

        $after = SemesterSchedulePlan::query()->where('semester_id', $semesterId)->where('status', 'published')->count();
        $this->assertSame($before, $after);
    }

    public function test_soft_scorer_penalizes_late_slots_more_with_morning_preference(): void
    {
        $scorer = new SoftConstraintScorer;

        $settings = ScheduleSettings::fromArray([
            'soft_constraints' => [
                'morning_preference' => ['enabled' => true, 'weight' => 2],
                'instructor_preferences' => ['enabled' => false, 'weight' => 1],
                'load_balance' => ['enabled' => false, 'weight' => 1],
                'avoid_back_to_back' => ['enabled' => false, 'weight' => 1],
                'student_gaps' => ['enabled' => false, 'weight' => 1],
                'department_proximity' => ['enabled' => false, 'weight' => 1],
                'max_daily_lectures' => ['enabled' => false, 'weight' => 1],
            ],
            'hard_constraints' => [],
            'capacity_threshold' => 0.9,
            'max_daily_lectures' => 5,
            'slot_minutes' => 50,
            'gap_minutes' => 10,
        ]);

        $event = new PlacementEvent(
            0,
            null,
            'course-a',
            'section-a',
            'instructor-a',
            'offering-a',
            'section-instructor-a',
            30,
            false,
            'C1',
            'Course 1'
        );

        $roomById = [
            'room-a' => new RoomRef('room-a', 50, false, 'available'),
        ];

        $grid = [
            new GridCell('sunday', 8 * 60, 8 * 60 + 50),
            new GridCell('sunday', 14 * 60, 14 * 60 + 50),
        ];

        $early = [0 => new Placement('sunday', 8 * 60, 8 * 60 + 50, 'room-a')];
        $late = [0 => new Placement('sunday', 14 * 60, 14 * 60 + 50, 'room-a')];

        $earlyPenalty = $scorer->penalty($early, [$event], $settings, $roomById, $grid, SchedulingContext::empty());
        $latePenalty = $scorer->penalty($late, [$event], $settings, $roomById, $grid, SchedulingContext::empty());

        $this->assertGreaterThan($earlyPenalty, $latePenalty);
    }
}
