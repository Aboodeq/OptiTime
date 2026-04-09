<?php

namespace App\Http\Controllers\Api\Coordinator;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CoordinatorLookupController extends Controller
{
    public function buildings(): JsonResponse
    {
        return response()->json([]);
    }

    public function roomTypes(): JsonResponse
    {
        return response()->json([
            ['value' => 'class', 'label' => 'Class'],
            ['value' => 'lab', 'label' => 'Lab'],
            ['value' => 'hall', 'label' => 'Hall'],
        ]);
    }

    public function courseTypes(): JsonResponse
    {
        return response()->json([]);
    }
}
