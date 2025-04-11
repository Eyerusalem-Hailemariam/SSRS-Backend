<?php


namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Shift;
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
     *         description="Staff or Shift not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Staff or Shift not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Duplicate scan or Scan not within shift time",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Duplicate scan or Scan not within shift time")
     *         )
     *     )
     * )
     */
    public function scan(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staff_id' => 'required|string',
            'mode' => 'required|string|in:checkin,checkout',
            'tolerance_minutes' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid input'], 422);
        }

        $staff = Staff::find($request->staff_id);

        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $currentTime = now();
        $todayDate = $currentTime->toDateString();


        $shift = Shift::where('staff_id', $staff->id)
            ->whereDate('start_date', '<=', $todayDate)
            ->whereDate('end_date', '>=', $todayDate)
            ->first();

        if (!$shift) {
            return response()->json(['message' => 'No shift assigned for today'], 404);
        }

        $shiftStart = \Carbon\Carbon::parse($shift->start_time);
        $shiftEnd = \Carbon\Carbon::parse($shift->end_time);

        $toleranceMinutes = $request->input('tolerance_minutes', config('attendance.tolerance_minutes', 15)); 

        $isStartTime = $currentTime->between(
            $shiftStart->copy()->subMinutes($toleranceMinutes),
            $shiftStart->copy()->addMinutes($toleranceMinutes)
        );

        $isEndTime = $currentTime->between(
            $shiftEnd->copy()->subMinutes($toleranceMinutes),
            $shiftEnd->copy()->addMinutes($toleranceMinutes)
        );

        if (($request->mode === 'checkin' && !$isStartTime) ||
            ($request->mode === 'checkout' && !$isEndTime)) {
            return response()->json(['message' => 'Scan not within shift time'], 409);
        }

        $existingScan = Attendance::where('staff_id', $staff->id)
            ->where('mode', $request->mode)
            ->whereDate('created_at', $todayDate)
            ->first();

        if ($existingScan) {
            return response()->json(['message' => 'Duplicate scan'], 409);
        }


        Attendance::create([
            'staff_id' => $staff->id,
            'mode' => $request->mode,
            'scanned_at' => $currentTime,
        ]);

        return response()->json(['message' => 'Scan recorded'], 201);
    }
}
