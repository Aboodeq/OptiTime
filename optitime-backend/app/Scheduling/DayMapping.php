<?php

namespace App\Scheduling;

final class DayMapping
{
    private const SHORT_TO_LONG = [
        'sun' => 'sunday',
        'mon' => 'monday',
        'tue' => 'tuesday',
        'wed' => 'wednesday',
        'thu' => 'thursday',
        'fri' => 'friday',
        'sat' => 'saturday',
    ];

    private const LONG_TO_SHORT = [
        'sunday' => 'sun',
        'monday' => 'mon',
        'tuesday' => 'tue',
        'wednesday' => 'wed',
        'thursday' => 'thu',
        'friday' => 'fri',
        'saturday' => 'sat',
    ];

    public static function toLong(string $day): string
    {
        $d = strtolower(trim($day));

        return self::SHORT_TO_LONG[$d] ?? $d;
    }

    public static function toShort(string $day): string
    {
        $d = strtolower(trim($day));

        return self::LONG_TO_SHORT[$d] ?? $d;
    }
}
