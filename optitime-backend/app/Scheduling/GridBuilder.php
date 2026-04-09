<?php

namespace App\Scheduling;





final class GridBuilder
{
    
    public function build(ScheduleSettings $settings): array
    {
        $slotLen = $settings->slotMinutes();
        $gap = $settings->gapMinutes();
        $dayStart = TimeUtil::toMinutes($settings->dayStart());
        $dayEnd = TimeUtil::toMinutes($settings->dayEnd());
        $breaks = $settings->breakTimes();

        $cells = [];
        foreach ($settings->enabledStudyDayKeys() as $day) {
            $t = $dayStart;
            while ($t + $slotLen <= $dayEnd) {
                $slotEnd = $t + $slotLen;
                if ($this->overlapsDayBreak($day, $t, $slotEnd, $breaks)) {
                    $t += $slotLen + $gap;

                    continue;
                }
                $cells[] = new GridCell($day, $t, $slotEnd);
                $t += $slotLen + $gap;
            }
        }

        return $cells;
    }

    


    private function overlapsDayBreak(string $day, int $slotStart, int $slotEnd, array $breaks): bool
    {
        foreach ($breaks as $b) {
            if ($b['day'] !== $day) {
                continue;
            }
            $bs = TimeUtil::toMinutes($b['start']);
            $be = TimeUtil::toMinutes($b['end']);
            if (TimeUtil::intervalsOverlap($slotStart, $slotEnd, $bs, $be)) {
                return true;
            }
        }

        return false;
    }
}
