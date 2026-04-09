<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateScheduleSettingCurrentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'capacity_threshold' => 'required|integer|min:0|max:100',
            'day_start' => 'required|date_format:H:i',
            'day_end' => 'required|date_format:H:i',
            'slot_minutes' => 'required|integer|min:15|max:240',
            'gap_minutes' => 'required|integer|min:0|max:120',
            'max_daily_lectures' => 'required|integer|min:1|max:12',
            'hard_constraints' => 'present|array',
            'hard_constraints.*.key' => 'required|string|max:100',
            'hard_constraints.*.enabled' => 'required|boolean',
            'soft_constraints' => 'present|array',
            'soft_constraints.*.key' => 'required|string|max:100',
            'soft_constraints.*.enabled' => 'required|boolean',
            'soft_constraints.*.weight' => 'nullable|numeric|min:0|max:999',
            'study_days' => 'present|array',
            'study_days.*.value' => 'required|string|max:10',
            'study_days.*.enabled' => 'required|boolean',
            'break_times' => 'present|array',
            'break_times.*.key' => 'required|string|max:100',
            'break_times.*.start' => 'required|date_format:H:i',
            'break_times.*.end' => 'required|date_format:H:i',
            'break_times.*.enabled' => 'required|boolean',
            'room_constraints' => 'present|array',
            'room_constraints.*.key' => 'required|string|max:100',
            'room_constraints.*.enabled' => 'required|boolean',
            'load_settings' => 'present|array',
            'load_settings.*.key' => 'required|string|max:100',
            'load_settings.*.min' => 'required|integer|min:0',
            'load_settings.*.max' => 'required|integer|min:0',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $data = $v->getData();
            $start = $data['day_start'] ?? '';
            $end = $data['day_end'] ?? '';
            if (is_string($start) && is_string($end) && $start !== '' && $end !== '' && $start >= $end) {
                $v->errors()->add('day_end', 'The day end must be after day start.');
            }

            $breaks = $data['break_times'] ?? [];
            if (is_array($breaks)) {
                foreach ($breaks as $i => $bt) {
                    if (! is_array($bt)) {
                        continue;
                    }
                    $s = $bt['start'] ?? '';
                    $e = $bt['end'] ?? '';
                    if (is_string($s) && is_string($e) && $s !== '' && $e !== '' && $s >= $e) {
                        $v->errors()->add("break_times.$i.end", 'Break end must be after start.');
                    }
                }
            }

            $loads = $data['load_settings'] ?? [];
            if (is_array($loads)) {
                foreach ($loads as $i => $lr) {
                    if (! is_array($lr)) {
                        continue;
                    }
                    $min = $lr['min'] ?? null;
                    $max = $lr['max'] ?? null;
                    if (is_numeric($min) && is_numeric($max) && (int) $min > (int) $max) {
                        $v->errors()->add("load_settings.$i.max", 'Load max must be >= min.');
                    }
                }
            }
        });
    }
}
