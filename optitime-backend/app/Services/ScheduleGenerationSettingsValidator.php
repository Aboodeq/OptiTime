<?php

namespace App\Services;

use App\Models\ScheduleSetting;
use App\Scheduling\ScheduleSettings;
use App\Scheduling\TimeUtil;

/**
 * Ensures schedule generation settings are fully defined (DB or inline payload)
 * before running placement algorithms.
 */
final class ScheduleGenerationSettingsValidator
{
    /** @var array<string, string> DB constraint_key => label */
    private const HARD_KEY_LABELS = [
        'no_instructor_overlap' => 'No instructor overlap',
        'no_room_overlap' => 'No room overlap',
        'no_section_overlap' => 'No section overlap',
        'room_capacity' => 'Room capacity',
        'room_status_available' => 'Room status availability',
        'lab_for_lab' => 'Lab room requirement',
        'working_hours' => 'Working hours',
        'instructor_availability' => 'Instructor availability',
        'capacity_threshold' => 'Capacity threshold',
    ];

    /** @var array<string, string> */
    private const SOFT_KEY_LABELS = [
        'instructor_preferences' => 'Instructor preferences',
        'load_balance' => 'Load balance',
        'avoid_back_to_back' => 'Avoid back-to-back',
        'student_gaps' => 'Student gaps',
        'morning_preference' => 'Morning preference',
        'department_proximity' => 'Department proximity',
        'max_daily_lectures' => 'Max daily lectures (soft)',
    ];

    /** @var list<string> */
    private const REQUIRED_HARD_KEYS = [
        'no_instructor_overlap',
        'no_room_overlap',
        'no_section_overlap',
        'room_capacity',
        'room_status_available',
        'lab_for_lab',
        'working_hours',
        'instructor_availability',
        'capacity_threshold',
    ];

    /** @var list<string> */
    private const REQUIRED_SOFT_KEYS = [
        'instructor_preferences',
        'load_balance',
        'avoid_back_to_back',
        'student_gaps',
        'morning_preference',
        'department_proximity',
        'max_daily_lectures',
    ];

    /**
     * @return array<string, mixed>|null Failure payload for ScheduleGenerateService, or null if OK.
     */
    public static function validateGenerationPrerequisites(?ScheduleSetting $dbRoot, ScheduleSettings $settings): ?array
    {
        $missingLabels = [];
        $missingKeys = [];

        if ($dbRoot !== null) {
            $dbRoot->loadMissing(['constraints', 'studyDays']);
            [$mk, $ml] = self::missingConstraintRowsFromDb($dbRoot);
            $missingKeys = array_merge($missingKeys, $mk);
            $missingLabels = array_merge($missingLabels, $ml);

            if ($dbRoot->studyDays->where('enabled', true)->isEmpty()) {
                $missingLabels[] = 'Study days (enable at least one day)';
                $missingKeys[] = 'study_days_enabled';
            }
        } else {
            [$mk, $ml] = self::missingConstraintKeysFromInlineSettings($settings);
            $missingKeys = array_merge($missingKeys, $mk);
            $missingLabels = array_merge($missingLabels, $ml);

            if ($settings->enabledStudyDayKeys() === []) {
                $missingLabels[] = 'Study days in schedule settings payload';
                $missingKeys[] = 'study_days';
            }
        }

        $missingLabels = array_merge($missingLabels, self::timeAndSlotIssues($settings));

        $missingLabels = array_values(array_unique(array_filter($missingLabels)));
        $missingKeys = array_values(array_unique($missingKeys));

        if ($missingLabels === []) {
            return null;
        }

        return [
            'success' => false,
            'reason' => 'Complete schedule settings before generation: '.implode(', ', $missingLabels).'.',
            'sessions' => [],
            'meta' => [
                'missing_settings' => $missingLabels,
                'missing_setting_keys' => $missingKeys,
            ],
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<string>} [keys, labels]
     */
    private static function missingConstraintRowsFromDb(ScheduleSetting $root): array
    {
        $hardExisting = $root->constraints->where('category', 'hard')->pluck('constraint_key')->map(fn ($k) => (string) $k)->all();
        $softExisting = $root->constraints->where('category', 'soft')->pluck('constraint_key')->map(fn ($k) => (string) $k)->all();

        $missingKeys = [];
        $labels = [];

        foreach (self::REQUIRED_HARD_KEYS as $key) {
            if (! in_array($key, $hardExisting, true)) {
                $missingKeys[] = 'hard:'.$key;
                $labels[] = 'Hard constraint: '.(self::HARD_KEY_LABELS[$key] ?? $key);
            }
        }
        foreach (self::REQUIRED_SOFT_KEYS as $key) {
            if (! in_array($key, $softExisting, true)) {
                $missingKeys[] = 'soft:'.$key;
                $labels[] = 'Soft constraint: '.(self::SOFT_KEY_LABELS[$key] ?? $key);
            }
        }

        return [$missingKeys, $labels];
    }

    /**
     * Inline JSON must declare every constraint key (enabled true/false), because disabled rows are omitted when loading from DB only.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    private static function missingConstraintKeysFromInlineSettings(ScheduleSettings $settings): array
    {
        $data = $settings->toArray();
        $hard = $data['hard_constraints'] ?? [];
        $soft = $data['soft_constraints'] ?? [];
        if (! is_array($hard)) {
            $hard = [];
        }
        if (! is_array($soft)) {
            $soft = [];
        }

        $missingKeys = [];
        $labels = [];

        foreach (self::REQUIRED_HARD_KEYS as $key) {
            if (! isset($hard[$key]) || ! is_array($hard[$key])) {
                $missingKeys[] = 'hard:'.$key;
                $labels[] = 'Hard constraint in payload: '.(self::HARD_KEY_LABELS[$key] ?? $key);
            }
        }
        foreach (self::REQUIRED_SOFT_KEYS as $key) {
            if (! isset($soft[$key]) || ! is_array($soft[$key])) {
                $missingKeys[] = 'soft:'.$key;
                $labels[] = 'Soft constraint in payload: '.(self::SOFT_KEY_LABELS[$key] ?? $key);
            }
        }

        return [$missingKeys, $labels];
    }

    /**
     * @return list<string>
     */
    private static function timeAndSlotIssues(ScheduleSettings $settings): array
    {
        $out = [];
        $start = TimeUtil::toMinutes($settings->dayStart());
        $end = TimeUtil::toMinutes($settings->dayEnd());
        if ($start >= $end) {
            $out[] = 'Day range (start time must be before end time)';
        }
        if ($settings->slotMinutes() < 1) {
            $out[] = 'Slot duration (slot_minutes must be >= 1)';
        }
        if ($settings->gapMinutes() < 0) {
            $out[] = 'Gap duration (gap_minutes cannot be negative)';
        }

        return $out;
    }
}
