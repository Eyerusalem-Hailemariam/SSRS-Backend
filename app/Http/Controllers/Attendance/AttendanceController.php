<?php


namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Models\StaffShift;
use Illuminate\Support\Carbon;

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
        'mode' => 'required|string|in:clock_in,clock_out',
        'tolerance_minutes' => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Invalid input'], 422);
    }

    $staff = Staff::where('staff_id', $request->staff_id)->first();
    if (!$staff) {
        return response()->json(['message' => 'Staff not found'], 404);
    }

    $currentTime = now();
    $todayDate = $currentTime->toDateString();

    $shifts = StaffShift::where('staff_id', $staff->id)
        ->whereDate('date', $todayDate)
        ->whereNotNull('start_time')
        ->whereNotNull('end_time')
        ->get();

    if ($shifts->isEmpty()) {
        return response()->json(['message' => 'No shift assigned for today'], 404);
    }

    $toleranceMinutes = $request->input('tolerance_minutes', config('attendance.tolerance_minutes', 15));

    $matchedShift = null;

    foreach ($shifts as $shift) {
        $shiftStart = Carbon::parse("{$shift->date} {$shift->start_time}");
        $shiftEnd = Carbon::parse("{$shift->date} {$shift->end_time}");

        $startWindow = $shiftStart->copy()->subMinutes($toleranceMinutes);
        $endWindow = $shiftStart->copy()->addMinutes($toleranceMinutes);
        $checkoutWindow = $shiftEnd->copy()->subMinutes($toleranceMinutes);
        $checkoutEndWindow = $shiftEnd->copy()->addMinutes($toleranceMinutes);

        if (
            ($request->mode === 'clock_in' && $currentTime->between($startWindow, $endWindow)) ||
            ($request->mode === 'clock_out' && $currentTime->between($checkoutWindow, $checkoutEndWindow))
        ) {
            $matchedShift = $shift;
            break;
        }
    }

    if (!$matchedShift) {
        return response()->json(['message' => 'Scan not within shift time'], 409);
    }

    $shiftStart = Carbon::parse("{$matchedShift->date} {$matchedShift->start_time}");
    $shiftEnd = Carbon::parse("{$matchedShift->date} {$matchedShift->end_time}");

    $existingScan = Attendance::where('staff_id', $staff->id)
        ->where('mode', $request->mode)
        ->whereBetween('scanned_at', [$shiftStart, $shiftEnd])
        ->first();

    if ($existingScan) {
        return response()->json(['message' => "Already {$request->mode}ed for this shift"], 409);
    }

    if ($request->mode === 'clock_out') {
        $checkinExists = Attendance::where('staff_id', $staff->id)
            ->where('mode', 'clock_in')
            ->whereBetween('scanned_at', [$shiftStart, $shiftEnd])
            ->exists();

        if (!$checkinExists) {
            return response()->json(['message' => 'Cannot check out without checking in first for this shift'], 409);
        }
    }

    Attendance::create([
        'staff_id' => $staff->id,
        'mode' => $request->mode,
        'scanned_at' => $currentTime,
    ]);

    return response()->json(['message' => 'Scan recorded'], 201);
}
 
    
}
