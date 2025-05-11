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
             'staff_shift_id' => 'required|exists:staff_shifts,id',
             'mode' => 'required|string|in:clock_in,clock_out',
             'tolerance_minutes' => 'nullable|integer|min:1',
         ]);
     
         if ($validator->fails()) {
             return response()->json(['message' => 'Invalid input', 'errors' => $validator->errors()], 422);
         }
     
         $staff = Staff::where('staff_id', $request->staff_id)->first();
         if (!$staff) {
             return response()->json(['message' => 'Staff not found'], 404);
         }
     
         $shift = StaffShift::find($request->staff_shift_id);
         if (!$shift || $shift->staff_id !== $staff->id) {
             return response()->json(['message' => 'Shift not assigned to this staff'], 403);
         }
     

         $existingAttendance = Attendance::where('staff_id', $staff->id)
             ->where('staff_shift_id', $shift->id)
             ->where('mode', $request->mode)
             ->first();
     
         if ($existingAttendance) {
             return response()->json([
                 'message' => 'Attendance already recorded for this mode and shift.',
                 'data' => $existingAttendance,
             ], 409);
         }
     
         if ($request->mode === 'clock_out') {
             $clockInRecord = Attendance::where('staff_id', $staff->id)
                 ->where('staff_shift_id', $shift->id)
                 ->where('mode', 'clock_in')
                 ->first();
     
             if (!$clockInRecord) {
                 return response()->json([
                     'message' => 'You must clock in before clocking out.',
                 ], 400); 
             }
         }
     
         $currentTime = now()->setTimezone('Africa/Nairobi');
         $toleranceMinutes = $request->input('tolerance_minutes', config('attendance.tolerance_minutes', 15));
     
         $isLate = false;
         $isEarly = false;
         $lateMinutes = 0;
         $earlyMinutes = 0;
     
         $shiftStart = Carbon::parse($shift->date . ' ' . $shift->start_time)->setTimezone('Africa/Nairobi');
         $shiftEnd = Carbon::parse($shift->date . ' ' . $shift->end_time)->setTimezone('Africa/Nairobi');
     
         if ($request->mode === 'clock_in') {
             $graceEnd = $shiftStart->copy()->addMinutes($toleranceMinutes);
             if ($currentTime->greaterThan($graceEnd)) {
                 $isLate = true;
                 $lateMinutes = $currentTime->diffInMinutes($graceEnd);
             }
         } elseif ($request->mode === 'clock_out') {
             $graceStart = $shiftEnd->copy()->subMinutes($toleranceMinutes);
             if ($currentTime->lessThan($graceStart)) {
                 $isEarly = true;
                 $earlyMinutes = $graceStart->diffInMinutes($currentTime);
             }
         }
     
         $attendance = new Attendance([
             'staff_id' => $staff->id,
             'staff_shift_id' => $shift->id,
             'mode' => $request->mode,
             'scanned_at' => $currentTime,
             'status' => 'present',
             'is_late' => $isLate,
             'late_minutes' => $lateMinutes,
             'is_early' => $isEarly,
             'early_minutes' => $earlyMinutes,
         ]);
     
         $attendance->save();
     
         return response()->json([
             'message' => 'Attendance recorded successfully',
             'data' => $attendance,
         ]);
     }


    public function markAbsentIfNotSignedIn()
{
    $today = Carbon::today();

    $shifts = StaffShift::whereDate('date', $today)->get();


    if ($shifts->isEmpty()) {
        return response()->json(['message' => 'No shifts found for today.'], 200);
    }
    
    foreach ($shifts as $shift) {
        $hasClockIn = Attendance::where('staff_id', $shift->staff_id)
            ->where('staff_shift_id', $shift->id)
            ->where('mode', 'clock_in')
            ->exists();

        if (!$hasClockIn) {
            Attendance::create([
                'staff_id' => $shift->staff_id,
                'staff_shift_id' => $shift->id,
                'mode' => 'absent',
                'scanned_at' => now()->setTimezone('Africa/Nairobi'),
                'status' => 'absent',
                'is_late' => false,
                'late_minutes' => 0,
                'is_early' => false,
                'early_minutes' => 0,
            ]);
        }
    }

    return response()->json(['message' => 'Absent employees have been updated successfully.']);
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
     
    }     