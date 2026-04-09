<?php

namespace App\Services;

use App\Models\ScheduleSetting;
use App\Models\ScheduleSettingBreakTime;
use App\Models\ScheduleSettingConstraint;
use App\Models\ScheduleSettingLoadRange;
use App\Models\ScheduleSettingRoomConstraint;
use App\Models\ScheduleSettingStudyDay;
use Illuminate\Support\Str;

final class ScheduleSettingAdminSerializer
{
    /** UI soft key => DB constraint_key */
    private const UI_TO_DB_SOFT = [
        'instructor_pref' => 'instructor_preferences',
        'student_gap' => 'student_gaps',
    ];

    /** DB constraint_key => UI soft key */
    private const DB_TO_UI_SOFT = [
        'instructor_preferences' => 'instructor_pref',
        'student_gaps' => 'student_gap',
    ];

    private static function formatTime(mixed $value): string
    {
        $s = (string) $value;

        return strlen($s) >= 5 ? substr($s, 0, 5) : $s;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toAdminArray(ScheduleSetting $root): array
    {
        $root->load(['constraints', 'roomConstraints', 'studyDays', 'breakTimes', 'loadRanges']);

        $hard = [];
        $soft = [];
        foreach ($root->constraints as $c) {
            if ($c->category === 'hard') {
                $hard[] = [
                    'key' => (string) $c->constraint_key,
                    'enabled' => (bool) $c->enabled,
                ];
            } elseif ($c->category === 'soft') {
                $dbKey = (string) $c->constraint_key;
                $uiKey = self::DB_TO_UI_SOFT[$dbKey] ?? $dbKey;
                $soft[] = [
                    'key' => $uiKey,
                    'enabled' => (bool) $c->enabled,
                    'weight' => $c->weight !== null ? (float) $c->weight : 1.0,
                ];
            }
        }

        $room = [];
        foreach ($root->roomConstraints as $rc) {
            $room[] = [
                'key' => (string) $rc->constraint_key,
                'enabled' => (bool) $rc->enabled,
            ];
        }

        $studyDays = [];
        foreach ($root->studyDays as $sd) {
            $studyDays[] = [
                'value' => (string) $sd->day_value,
                'enabled' => (bool) $sd->enabled,
            ];
        }

        $breaks = [];
        foreach ($root->breakTimes as $bt) {
            $breaks[] = [
                'key' => (string) $bt->break_key,
                'start' => self::formatTime($bt->start_time),
                'end' => self::formatTime($bt->end_time),
                'enabled' => (bool) $bt->enabled,
            ];
        }

        $loads = [];
        foreach ($root->loadRanges as $lr) {
            $loads[] = [
                'key' => (string) $lr->load_key,
                'min' => (int) $lr->min_value,
                'max' => (int) $lr->max_value,
            ];
        }

        return [
            'id' => $root->id,
            'capacity_threshold' => (int) $root->capacity_threshold,
            'day_start' => self::formatTime($root->day_start),
            'day_end' => self::formatTime($root->day_end),
            'slot_minutes' => (int) $root->slot_minutes,
            'gap_minutes' => (int) $root->gap_minutes,
            'max_daily_lectures' => (int) $root->max_daily_lectures,
            'hard_constraints' => $hard,
            'soft_constraints' => $soft,
            'study_days' => $studyDays,
            'break_times' => $breaks,
            'room_constraints' => $room,
            'load_settings' => $loads,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function syncFromAdminArray(ScheduleSetting $root, array $data): void
    {
        $root->update([
            'day_start' => $data['day_start'],
            'day_end' => $data['day_end'],
            'slot_minutes' => $data['slot_minutes'],
            'gap_minutes' => $data['gap_minutes'],
            'max_daily_lectures' => $data['max_daily_lectures'],
            'capacity_threshold' => $data['capacity_threshold'],
        ]);

        $sid = $root->id;

        ScheduleSettingConstraint::query()->where('schedule_setting_id', $sid)->delete();
        ScheduleSettingRoomConstraint::query()->where('schedule_setting_id', $sid)->delete();
        ScheduleSettingStudyDay::query()->where('schedule_setting_id', $sid)->delete();
        ScheduleSettingBreakTime::query()->where('schedule_setting_id', $sid)->delete();
        ScheduleSettingLoadRange::query()->where('schedule_setting_id', $sid)->delete();

        foreach ($data['hard_constraints'] ?? [] as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key === '') {
                continue;
            }
            ScheduleSettingConstraint::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'category' => 'hard',
                'constraint_key' => $key,
                'enabled' => (bool) ($row['enabled'] ?? true),
                'weight' => 1,
            ]);
        }

        foreach ($data['soft_constraints'] ?? [] as $row) {
            $uiKey = (string) ($row['key'] ?? '');
            if ($uiKey === '') {
                continue;
            }
            $dbKey = self::UI_TO_DB_SOFT[$uiKey] ?? $uiKey;
            ScheduleSettingConstraint::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'category' => 'soft',
                'constraint_key' => $dbKey,
                'enabled' => (bool) ($row['enabled'] ?? true),
                'weight' => isset($row['weight']) ? (float) $row['weight'] : 1.0,
            ]);
        }

        foreach ($data['room_constraints'] ?? [] as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key === '') {
                continue;
            }
            ScheduleSettingRoomConstraint::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'constraint_key' => $key,
                'enabled' => (bool) ($row['enabled'] ?? true),
            ]);
        }

        foreach ($data['study_days'] ?? [] as $row) {
            $day = (string) ($row['value'] ?? '');
            if ($day === '') {
                continue;
            }
            ScheduleSettingStudyDay::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'day_value' => $day,
                'enabled' => (bool) ($row['enabled'] ?? true),
            ]);
        }

        foreach ($data['break_times'] ?? [] as $row) {
            $bk = (string) ($row['key'] ?? '');
            if ($bk === '') {
                continue;
            }
            ScheduleSettingBreakTime::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'break_key' => $bk,
                'start_time' => $row['start'],
                'end_time' => $row['end'],
                'enabled' => (bool) ($row['enabled'] ?? true),
            ]);
        }

        foreach ($data['load_settings'] ?? [] as $row) {
            $lk = (string) ($row['key'] ?? '');
            if ($lk === '') {
                continue;
            }
            ScheduleSettingLoadRange::query()->create([
                'id' => (string) Str::uuid(),
                'schedule_setting_id' => $sid,
                'load_key' => $lk,
                'min_value' => (int) ($row['min'] ?? 0),
                'max_value' => (int) ($row['max'] ?? 0),
            ]);
        }
    }

    /**
     * Minimal schedule fields for instructor availability UI (no admin-only data).
     *
     * @return array<string, mixed>
     */
    public static function toInstructorAvailabilityGridContext(ScheduleSetting $root): array
    {
        $root->load(['studyDays', 'breakTimes']);

        $studyDays = [];
        foreach ($root->studyDays as $sd) {
            $studyDays[] = [
                'value' => (string) $sd->day_value,
                'enabled' => (bool) $sd->enabled,
            ];
        }

        $breaks = [];
        foreach ($root->breakTimes as $bt) {
            $breaks[] = [
                'key' => (string) $bt->break_key,
                'start' => self::formatTime($bt->start_time),
                'end' => self::formatTime($bt->end_time),
                'enabled' => (bool) $bt->enabled,
            ];
        }

        return [
            'day_start' => self::formatTime($root->day_start),
            'day_end' => self::formatTime($root->day_end),
            'slot_minutes' => (int) $root->slot_minutes,
            'gap_minutes' => (int) $root->gap_minutes,
            'max_daily_lectures' => (int) $root->max_daily_lectures,
            'study_days' => $studyDays,
            'break_times' => $breaks,
        ];
    }

    /**
     * Read-only weekly board + settings row id for generation (no admin constraint payloads).
     * Safe for instructor/student/coordinator schedule UIs without schedule_settings.view.
     *
     * @return array<string, mixed>
     */
    public static function toWeeklyBoardContext(): array
    {
        $row = ScheduleSetting::query()->orderBy('created_at')->first();
        if ($row === null) {
            return [
                'settings_id' => null,
                'day_start' => '08:00',
                'day_end' => '16:00',
                'slot_minutes' => 60,
                'gap_minutes' => 0,
                'max_daily_lectures' => 0,
                'study_days' => [],
                'break_times' => [],
            ];
        }

        return array_merge(
            ['settings_id' => $row->id],
            self::toInstructorAvailabilityGridContext($row)
        );
    }
}
