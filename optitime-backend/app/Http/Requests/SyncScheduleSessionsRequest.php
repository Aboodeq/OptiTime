<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncScheduleSessionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sessions' => ['required', 'array'],
            'sessions.*.room_id' => ['required', 'uuid', 'exists:rooms,id'],
            'sessions.*.section_instructor_id' => ['required', 'uuid', 'exists:course_section_instructors,id'],
            'sessions.*.course_offering_id' => ['required', 'uuid', 'exists:course_offerings,id'],
            'sessions.*.day' => ['required', 'string', 'max:32'],
            'sessions.*.start' => ['required', 'string', 'max:8'],
            'sessions.*.end' => ['required', 'string', 'max:8'],
        ];
    }
}
