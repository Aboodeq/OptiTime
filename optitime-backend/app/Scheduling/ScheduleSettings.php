<?php

namespace App\Scheduling;




final class ScheduleSettings
{
    
    public function __construct(
        private array $data
    ) {}

    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    public static function fromJsonFile(string $path): self
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException("Cannot read settings file: {$path}");
        }
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return new self($decoded);
    }

    
    public function toArray(): array
    {
        return $this->data;
    }

    public function slotMinutes(): int
    {
        return (int) ($this->data['slot_minutes'] ?? 50);
    }

    public function gapMinutes(): int
    {
        return (int) ($this->data['gap_minutes'] ?? 10);
    }

    public function dayStart(): string
    {
        return (string) ($this->data['day_start'] ?? '08:00');
    }

    public function dayEnd(): string
    {
        return (string) ($this->data['day_end'] ?? '18:00');
    }

    
    public function enabledStudyDayKeys(): array
    {
        $study = $this->data['study_days'] ?? [];
        if (! is_array($study)) {
            return [];
        }
        $out = [];
        foreach ($study as $day => $enabled) {
            if ($enabled) {
                $out[] = strtolower((string) $day);
            }
        }

        return $out;
    }

    
    public function breakTimes(): array
    {
        $bt = $this->data['break_times'] ?? [];
        if (! is_array($bt)) {
            return [];
        }
        $out = [];
        foreach ($bt as $row) {
            if (! is_array($row)) {
                continue;
            }
            $out[] = [
                'day' => strtolower((string) ($row['day'] ?? '')),
                'start' => (string) ($row['start'] ?? '00:00'),
                'end' => (string) ($row['end'] ?? '00:00'),
            ];
        }

        return $out;
    }

    public function isHardEnabled(string $key): bool
    {
        $hc = $this->data['hard_constraints'] ?? [];
        if (! is_array($hc) || ! isset($hc[$key]) || ! is_array($hc[$key])) {
            return false;
        }

        return ! empty($hc[$key]['enabled']);
    }

    
    public function getSoft(string $key): array
    {
        $sc = $this->data['soft_constraints'] ?? [];
        if (! is_array($sc) || ! isset($sc[$key]) || ! is_array($sc[$key])) {
            return ['enabled' => false, 'weight' => 0.0];
        }
        $row = $sc[$key];

        return [
            'enabled' => ! empty($row['enabled']),
            'weight' => (float) ($row['weight'] ?? 0),
        ];
    }

    public function maxDailyLectures(): int
    {
        return (int) ($this->data['max_daily_lectures'] ?? 5);
    }

    public function capacityThreshold(): float
    {
        return (float) ($this->data['capacity_threshold'] ?? 0.9);
    }

    
    public function isCapacityThresholdHard(): bool
    {
        return $this->isHardEnabled('capacity_threshold');
    }

    


    public function isRoomAllowedForCourse(string $courseId, string $roomId): bool
    {
        $rc = $this->data['room_constraints'] ?? [];
        if (! is_array($rc) || $rc === []) {
            return true;
        }
        $by = $rc['by_course_id'] ?? [];
        if (is_array($by) && $by !== []) {
            $allowed = $by[$courseId] ?? null;
            if (is_array($allowed)) {
                return in_array($roomId, $allowed, true);
            }
        }
        $global = $rc['allowed_room_ids'] ?? null;
        if (is_array($global) && $global !== []) {
            return in_array($roomId, $global, true);
        }

        return true;
    }

    
    public function loadSettings(): array
    {
        $ls = $this->data['load_settings'] ?? [];

        return is_array($ls) ? $ls : [];
    }
}
