<?php

namespace App\Http\Controllers\Shift;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StaffShift;
use App\Models\Shift;
use App\Models\Staff;
use App\Notifications\ShiftUpdated;
use Andegna\DateTimeFactory;
use Illuminate\Support\Carbon;

class StaffShiftController extends Controller
{


public function store(Request $request)
{
    $request->validate([
        'staff_id' => 'required|exists:staff,id',
        'shift_id' => 'required|exists:shifts,id',
        'date' => 'required|date|after_or_equal:today',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i',
        'is_overtime' => 'nullable|boolean',
        'overtime_type' => 'nullable|in:normal,holiday,weekend,night',
        'is_night_shift' => 'nullable|boolean', 
    ]);

    $shift = Shift::findOrFail($request->shift_id);

    $startTime = $request->start_time ?? $shift->start_time;
    $endTime = $request->end_time ?? $shift->end_time;
    $isNightShift = $request->boolean('is_night_shift');

   
    if ($startTime > $endTime && !$isNightShift) {
        return response()->json([
            'message' => 'End time is before start time. If this is a night shift, please confirm by checking "Night Shift".',
        ], 422);
    }

    $startDateTime = \Carbon\Carbon::parse($request->date . ' ' . $startTime);
    $endDateTime = $isNightShift
        ? \Carbon\Carbon::parse($request->date . ' ' . $endTime)->addDay() 
        : \Carbon\Carbon::parse($request->date . ' ' . $endTime);

    
    if ($request->date == now()->toDateString()) {
        if ($startDateTime < now()) {
            return response()->json([
                'message' => 'Start time cannot be in the past.',
            ], 422);
        }
        if ($endDateTime < now()) {
            return response()->json([
                'message' => 'End time cannot be in the past.',
            ], 422);
        }
    }

   
    if ($this->hasShiftConflict($request->staff_id, $request->date, $startTime, $endTime)) {
        return response()->json([
            'message' => 'Shift time overlaps with an existing staff assignment.',
        ], 409);
    }

    $overtime = $request->has('is_overtime') ? $shift->is_overtime : ($request->boolean('is_overtime') ? 1 : 0);
    $overtimeType = $request->overtime_type ?? ($shift->is_overtime ? $shift->overtime_type : null);

    if ($overtime && !$overtimeType) {
        return response()->json([
            'message' => 'Overtime type is required when overtime is applied.',
        ], 422);
    }

    $staffShift = StaffShift::create([
        'staff_id' => $request->staff_id,
        'shift_id' => $request->shift_id,
        'date' => $request->date,
        'start_time' => $startDateTime->format('H:i'),
        'end_time' => $endDateTime->format('H:i'),
        'is_overtime' => $overtime,
        'overtime_type' => $overtime ? $overtimeType : null,
        'is_night_shift' => $isNightShift,
    ]);

    return response()->json($staffShift, 201);
}

                
    public function update(Request $request, $id)
    {
    $request->validate([
        'staff_id' => 'sometimes|exists:staff,id',
        'shift_id' => 'sometimes|exists:shifts,id',
        'date' => 'sometimes|date|after_or_equal:today',
        'start_time' => 'nullable|date_format:H:i',
        'end_time' => 'nullable|date_format:H:i|after:start_time',
        'is_overtime' => 'nullable|boolean',
    ]);

    $staffShift = StaffShift::findOrFail($id);

    $staffId = $request->staff_id ?? $staffShift->staff_id;
    $shiftId = $request->shift_id ?? $staffShift->shift_id;
    $date = $request->date ?? $staffShift->date;

    $shift = Shift::findOrFail($shiftId);

    $startTime = $request->start_time ?? $staffShift->start_time ?? $shift->start_time;
    $endTime = $request->end_time ?? $staffShift->end_time ?? $shift->end_time;
    $overtime = $request->has('is_overtime') ? ($request->boolean('is_overtime') ? 1 : 0) : $staffShift->is_overtime;


    if ($date == now()->toDateString()) {
        if ($startTime < now()->format('H:i')) {
            return response()->json([
                'message' => 'Start time cannot be in the past for today.',
            ], 422);
        }

        if ($endTime < now()->format('H:i')) {
            return response()->json([
                'message' => 'End time cannot be in the past for today.',
            ], 422);
        }
    }

    if ($this->hasShiftConflict($staffId, $date, $startTime, $endTime, $id)) {
        return response()->json([
            'message' => 'Shift time overlaps with an existing assignment.',
        ], 409);
    }

    $staffShift->update([
        'staff_id' => $staffId,
        'shift_id' => $shiftId,
        'date' => $date,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'is_overtime' => $overtime,
    ]);

    return response()->json($staffShift, 200);
    }


    public function destroy($id)
    {
        $staffShift = StaffShift::findOrFail($id);
        $staffShift->delete();
        return response()->json([
            'message' => 'Staff Shift deleted successfully.',
        ], 200);
    }

    public function index()
    {
        $staffShifts = StaffShift::all();
        return response()->json($staffShifts, 200);
    }

   
    public function getStaffShift($staffId)
    {
        $staffShift = StaffShift::where('staff_id', $staffId)->get();
        return response()->json($staffShift, 200);
    }

   private function hasShiftConflict($staffId, $date, $startTime, $endTime, $excludeId = null)
{
    $startDateTime = Carbon::parse("$date $startTime");
    $endDateTime = Carbon::parse("$date $endTime");
    if ($endDateTime <= $startDateTime) {
        $endDateTime->addDay();
    }

    $query = StaffShift::where('staff_id', $staffId)
        ->whereDate('date', $date)
        ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
        ->get();

    foreach ($query as $existingShift) {
        $existingStart = Carbon::parse($existingShift->date . ' ' . $existingShift->start_time);
        $existingEnd = Carbon::parse($existingShift->date . ' ' . $existingShift->end_time);
        if ($existingEnd <= $existingStart) {
            $existingEnd->addDay();
        }

      
        if ($startDateTime < $existingEnd && $endDateTime > $existingStart) {
            return true;
        }
    }

    return false;
}

}
