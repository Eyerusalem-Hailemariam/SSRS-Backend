<?php
namespace App\Http\Controllers\Attendance;

use App\Http\Controllers\Controller;
use OpenApi\Annotations as OA;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Staff;
use App\Models\StaffShift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class AttendanceController extends Controller
{
    /**
     * Record staff attendance (clock-in/clock-out).
     *
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
        'tolerance_minutes' => 'nullable|integer|min:0',
    ]);

    if ($validator->fails()) {
        return response()->json(['message' => 'Invalid input', 'errors' => $validator->errors()], 422);
    }

    $staff = Staff::where('staff_id', $request->staff_id)->first();
    if (!$staff) {
        return response()->json(['message' => 'Staff not found'], 404);
    }

    $shifts = StaffShift::where('staff_id', $staff->id)
        ->whereIn('date', [Carbon::today()->toDateString(), Carbon::yesterday()->toDateString()])
        ->get();

    if ($shifts->isEmpty()) {
        return response()->json(['message' => 'No shift found for today or night shift from yesterday'], 404);
    }

    $currentTime = now()->setTimezone('Africa/Nairobi')->setSeconds(0)->setMicroseconds(0);
    $toleranceMinutes = $request->input('tolerance_minutes', config('attendance.tolerance_minutes', 1));

    foreach ($shifts as $shift) {
        $shiftStart = Carbon::parse($shift->date . ' ' . $shift->start_time)
            ->setTimezone('Africa/Nairobi')->setSeconds(0)->setMicroseconds(0);

        $shiftEnd = Carbon::parse($shift->date . ' ' . $shift->end_time)
            ->setTimezone('Africa/Nairobi')->setSeconds(0)->setMicroseconds(0);

        if ($shift->is_night_shift) {
            $shiftEnd->addDay();
        }

        if ($currentTime->gte($shiftStart) && $currentTime->lte($shiftEnd)) {

            $existingAttendance = Attendance::where('staff_id', $staff->id)
                ->where('mode', $request->mode)
                ->whereBetween('scanned_at', [$shiftStart, $shiftEnd])
                ->first();

            if ($existingAttendance) {
                return response()->json([
                    'message' => 'Attendance already recorded for this shift',
                    'existing' => $existingAttendance
                ], 409);
            }

            if ($request->mode === 'clock_out') {
                $existingClockIn = Attendance::where('staff_id', $staff->id)
                    ->where('mode', 'clock_in')
                    ->whereBetween('scanned_at', [$shiftStart, $shiftEnd])
                    ->first();

                if (!$existingClockIn) {
                    return response()->json([
                        'message' => 'You must clock in before clocking out for this shift'
                    ], 403);
                }
            }

            if ($request->mode === 'clock_in') {
                $graceEnd = $shiftStart->copy()->addMinutes($toleranceMinutes);
                $isLate = $currentTime->greaterThan($graceEnd);
                $lateMinutes = $isLate ? $currentTime->diffInMinutes($graceEnd) : 0;

                $attendance = Attendance::create([
                    'staff_id' => $staff->id,
                    'staff_shift_id' => $shift->id,
                    'mode' => 'clock_in',
                    'scanned_at' => $currentTime,
                    'status' => 'present',
                    'is_late' => $isLate,
                    'late_minutes' => $lateMinutes,
                    'approved_by_admin' => false,
                ]);
            } else {
                $graceStart = $shiftEnd->copy()->subMinutes($toleranceMinutes);
                $isEarly = $currentTime->lessThan($graceStart);
                $earlyMinutes = $isEarly ? $graceStart->diffInMinutes($currentTime) : 0;

                $attendance = Attendance::create([
                    'staff_id' => $staff->id,
                    'staff_shift_id' => $shift->id,
                    'mode' => 'clock_out',
                    'scanned_at' => $currentTime,
                    'status' => 'present',
                    'is_early' => $isEarly,
                    'early_minutes' => $earlyMinutes,
                    'late_minutes' => 0,
                    'approved_by_admin' => false,
                ]);
            }

            return response()->json([
                'message' => 'Attendance recorded successfully',
                'data' => $attendance,
            ]);
        }
    }

    return response()->json(['message' => 'Current time is not within any shift'], 400);
}

    
     
     public function approveAttendance(Request $request, $attendanceId)
     {
         $attendance = Attendance::find($attendanceId);
         if (!$attendance) {
             return response()->json(['message' => 'Attendance record not found'], 404);
         }
     
         $attendance->approved_by_admin = !$attendance->approved_by_admin;
         $attendance->save();
     
         return response()->json([
             'message' => 'Attendance approved by admin',
             'data' => $attendance,
         ]);
     }
    
     public function approvelate(Request $request, $attendanceId)
     {
         $attendance = Attendance::find($attendanceId);
         if (!$attendance) {
             return response()->json(['message' => 'Attendance record not found'], 404);
         }
     
         $attendance->late_approved = !$attendance->late_approved;
         $attendance->save();
     
         return response()->json([
             'message' => 'Late minutes approved by admin',
             'data' => $attendance,
         ]);
     }

     public function approveearly(Request $request, $attendanceId)
     {
         $attendance = Attendance::find($attendanceId);
         if (!$attendance) {
             return response()->json(['message' => 'Attendance record not found'], 404);
         }
     
         $attendance->early_approved = !$attendance->early_approved;
         $attendance->save();
     
         return response()->json([
             'message' => 'Early minutes approved by admin',
             'data' => $attendance,
         ]);
     }

    public function getStaffAttendance($staff_id)
    {
        $staff = Staff::where('id', $staff_id)->first();
        if (!$staff) {
            return response()->json(['message' => 'Staff not found'], 404);
        }

        $attendance = Attendance::where('staff_id', $staff_id)->get();
        return response()->json(['attendance' => $attendance], 200);
    }

public function markAbsent()
{
    $today = Carbon::today();
    $shifts = StaffShift::all();

    if ($shifts->isEmpty()) {
        return response()->json(['message' => 'No shifts found for today.'], 404);
    }

    $absentCount = 0;

    foreach ($shifts as $shift) {
        $hasClockIn = Attendance::where('staff_id', $shift->staff_id)
            ->where('staff_shift_id', $shift->id)
            ->where('mode', 'clock_in')
            ->exists();

        if (!$hasClockIn) {
            // Construct scanned_at using the shift's date and start_time
            $scannedAt = Carbon::parse($shift->date . ' ' . $shift->start_time)
                ->setTimezone('Africa/Nairobi')
                ->setSeconds(0)
                ->setMicroseconds(0);
        }

        if (!$hasClockIn) {
            Attendance::create([
                'staff_id' => $shift->staff_id,
                'staff_shift_id' => $shift->id,
                'mode' => 'clock_in',
                'scanned_at' => $scannedAt,
                'status' => 'absent',
                'is_late' => false,
                'late_minutes' => 0,
                'is_early' => false,
                'early_minutes' => 0,
                'approved_by_admin' => false,
            ]);
            $absentCount++;
        }
    }

    return response()->json(['message' => "Absent staff marked successfully.", 'absent_count' => $absentCount], 200);
}

}     