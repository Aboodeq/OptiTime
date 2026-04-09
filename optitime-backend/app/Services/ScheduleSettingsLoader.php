<?php

namespace App\Services;

use App\Models\ScheduleSetting;
use App\Scheduling\DayMapping;
use App\Scheduling\ScheduleSettings;

final class ScheduleSettingsLoader
{
    public static function toSolverArray(ScheduleSetting $root): array
    {
        $root->load(['studyDays', 'breakTimes', 'constraints', 'roomConstraints', 'loadRanges']);

        $studyDays = [];
        foreach ($root->studyDays as $row) {
            if ($row->enabled) {
                $studyDays[DayMapping::toLong($row->day_value)] = true;
            }
        }

        $breakTimes = [];
        foreach ($root->breakTimes as $bt) {
            if (! $bt->enabled) {
                continue;
            }
            $start = substr((string) $bt->start_time, 0, 5);
            $end = substr((string) $bt->end_time, 0, 5);
            foreach ($root->studyDays as $sd) {
                if (! $sd->enabled) {
                    continue;
                }
                $breakTimes[] = [
                    'day' => DayMapping::toLong($sd->day_value),
                    'start' => $start,
                    'end' => $end,
                ];
            }
        }

        $hardConstraints = [];
        $softConstraints = [];
        foreach ($root->constraints as $c) {
            if (! $c->enabled) {
                continue;
            }
            $key = $c->constraint_key;
            if ($c->category === 'soft') {
                $softConstraints[$key] = [
                    'enabled' => true,
                    'weight' => (float) ($c->weight ?? 1),
                ];
            } else {
                $hardConstraints[$key] = ['enabled' => true];
            }
        }

        $roomConstraints = [];
        foreach ($root->roomConstraints as $rc) {
            if ($rc->enabled) {
                $roomConstraints[$rc->constraint_key] = true;
            }
        }

        $loadSettings = [];
        foreach ($root->loadRanges as $lr) {
            $loadSettings[$lr->load_key] = [
                'min' => $lr->min_value,
                'max' => $lr->max_value,
            ];
        }

        return [
            'study_days' => $studyDays,
            'day_start' => substr((string) $root->day_start, 0, 5),
            'day_end' => substr((string) $root->day_end, 0, 5),
            'slot_minutes' => (int) $root->slot_minutes,
            'gap_minutes' => (int) $root->gap_minutes,
            'break_times' => $breakTimes,
            'hard_constraints' => $hardConstraints,
            'soft_constraints' => $softConstraints,
            'room_constraints' => $roomConstraints,
            'load_settings' => $loadSettings,
            'capacity_threshold' => max(0.0, min(1.0, ((int) $root->capacity_threshold) / 100)),
            'max_daily_lectures' => (int) $root->max_daily_lectures,
        ];
    }

    public static function toScheduleSettings(ScheduleSetting $root): ScheduleSettings
    {
        return ScheduleSettings::fromArray(self::toSolverArray($root));
    }

    public static function firstOrFail(): ScheduleSettings
    {
        $row = ScheduleSetting::query()->orderBy('created_at')->firstOrFail();

        return self::toScheduleSettings($row);
    }
}
