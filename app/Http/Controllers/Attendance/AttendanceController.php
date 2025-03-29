<?php

namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA; 
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Staff;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
/**
     * @OA\Post(
     *     path="/api/scan",
     *     summary="Record staff attendance",
     *     description="Scans staff attendance by staff ID and mode.",
     *     tags={"Attendance"},
     *     security={{"sanctum":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"staff_id", "mode"},
     *             @OA\Property(property="staff_id", type="string", example="12345"),
     *             @OA\Property(property="mode", type="string", example="checkin")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Scan recorded",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Scan recorded")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Staff not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate scan",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Duplicate scan")
     *         )
     *     )
     * )
     */
    public function scan(Request $request) {
        $request->validate([
            'staff_id' => 'required|string',
            'mode' => 'required|string',
        ]);

    $staff = Staff::where('staff_id', $request->staff_id)->first();

    if (!$staff) {
        return response()->json([
            'message' => 'Staff not found. please re-scan',
            'retry' => true
        ], 404);
    }

    $attendance = Attendance::where('staff_id', $staff->id)
        ->where('mode', $request->mode)
        ->whereDate('scanned_at', [now()=>startOfDay(), now()=>endOfDay()])
        ->first();

    if ($attendance) {
        return response()->json([
            'message' => 'Duplicate scan'
        ], 409);
    }


    $attendance = Attendance::create([
        'staff_id' => $staff->id,
        'mode' => $request->mode,
        'scanned_at' => now()
    ]);

    return response()->json(['message' => 'Scan recorded'], 201);
    }
}