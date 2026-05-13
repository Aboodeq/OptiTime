<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'algorithm' => 'required|string|in:genetic,backtracking',
            'semester_id' => 'required|uuid|exists:semesters,id',
            'schedule_settings' => 'nullable|array',
            'settings_id' => 'nullable|uuid|exists:schedule_settings,id',
            'baseDraft' => 'nullable|array',
            'baseDraft.sessions' => 'nullable|array',
            'rooms_override' => 'nullable|array',
            'rooms_override.*.id' => 'uuid',
            'rooms_override.*.capacity' => 'integer|min:0',
            'rooms_override.*.is_lab' => 'boolean',
            'rooms_override.*.status' => 'nullable|string|max:30',
            'seed' => 'nullable|integer',
            'instructor_availabilities' => 'nullable|array',
            'instructor_availabilities.*.instructor_id' => 'required|uuid',
            'instructor_availabilities.*.day_of_week' => 'required|string|max:32',
            'instructor_availabilities.*.start' => 'required|string|max:8',
            'instructor_availabilities.*.end' => 'required|string|max:8',
            'instructor_availabilities.*.status' => 'nullable|string|max:20',
        ];
    }
}
