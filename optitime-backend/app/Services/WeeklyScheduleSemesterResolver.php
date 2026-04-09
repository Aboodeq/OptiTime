<?php

namespace App\Services;

use App\Models\Semester;
use Illuminate\Http\Request;

final class WeeklyScheduleSemesterResolver
{
    /**
     * Resolve semester for weekly schedule APIs: explicit semester_id or active semester.
     */
    public static function resolveSemesterId(Request $request): ?string
    {
        if ($request->filled('semester_id')) {
            $request->validate([
                'semester_id' => ['uuid', 'exists:semesters,id'],
            ]);

            return $request->string('semester_id')->toString();
        }

        return Semester::query()
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->value('id');
    }

    /**
     * @return array{id: string, name: string, code: string, is_active: bool}|null
     */
    public static function semesterPayload(?string $semesterId): ?array
    {
        if ($semesterId === null || $semesterId === '') {
            return null;
        }
        $row = Semester::query()->find($semesterId);
        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'name' => (string) $row->name,
            'code' => (string) $row->code,
            'is_active' => (bool) $row->is_active,
        ];
    }
}
