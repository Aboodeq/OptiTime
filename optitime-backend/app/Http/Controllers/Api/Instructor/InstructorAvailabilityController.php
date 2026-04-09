<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorAvailabilityCell;
use App\Models\InstructorAvailabilityProfile;
use App\Scheduling\DayMapping;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstructorAvailabilityController extends Controller
{
    private function instructorId(Request $request): string
    {
        $ins = $request->user()->instructor;
        abort_if(! $ins, 403, 'User is not an instructor.');

        return (string) $ins->id;
    }

    private function profileForInstructor(string $instructorId): InstructorAvailabilityProfile
    {
        return InstructorAvailabilityProfile::query()->firstOrCreate(
            ['instructor_id' => $instructorId],
            ['notes' => null]
        );
    }

    public function index(Request $request): JsonResponse
    {
        $id = $this->instructorId($request);
        $profile = $this->profileForInstructor($id);

        return response()->json(
            $profile->cells()->orderBy('day_value')->orderBy('start_time')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $id = $this->instructorId($request);
        $profile = $this->profileForInstructor($id);
        $data = $request->validate([
            'day_of_week' => 'required|string|max:16',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'status' => 'sometimes|string|max:20',
        ]);
        $row = $profile->cells()->create([
            'day_value' => DayMapping::toShort($data['day_of_week']),
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'status' => $data['status'] ?? 'preferred',
        ]);
        AuditLogger::log($request->user(), 'instructor_availability.create', InstructorAvailabilityCell::class, $row->id, $data, $request);

        return response()->json($row, 201);
    }

    public function update(Request $request, string $availability): JsonResponse
    {
        $id = $this->instructorId($request);
        $profile = $this->profileForInstructor($id);
        $row = InstructorAvailabilityCell::query()
            ->where('availability_profile_id', $profile->id)
            ->findOrFail($availability);
        $data = $request->validate([
            'day_of_week' => 'sometimes|string|max:16',
            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',
            'status' => 'sometimes|string|max:20',
        ]);
        if (isset($data['day_of_week'])) {
            $data['day_value'] = DayMapping::toShort($data['day_of_week']);
            unset($data['day_of_week']);
        }
        $row->update($data);
        AuditLogger::log($request->user(), 'instructor_availability.update', InstructorAvailabilityCell::class, $availability, $data, $request);

        return response()->json($row->fresh());
    }

    public function destroy(Request $request, string $availability): JsonResponse
    {
        $id = $this->instructorId($request);
        $profile = $this->profileForInstructor($id);
        $row = InstructorAvailabilityCell::query()
            ->where('availability_profile_id', $profile->id)
            ->findOrFail($availability);
        $row->delete();
        AuditLogger::log($request->user(), 'instructor_availability.delete', InstructorAvailabilityCell::class, $availability, null, $request);

        return response()->json(['deleted' => true]);
    }
}
