<?php

namespace App\Http\Controllers\Shift;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\StaffShift;
use App\Models\Shift;
use App\Models\Staff;
use App\Notifications\ShiftUpdated;

class StaffShiftController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'shift_id' => 'required|exists:shifts,id',
            'date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ]);
    
        $shift = Shift::findOrFail($request->shift_id);
    
        $startTime = $request->start_time ?? $shift->start_time;
        $endTime = $request->end_time ?? $shift->end_time;
    
      
        if ($this->hasShiftConflict($request->staff_id, $request->date, $startTime, $endTime)) {
            return response()->json([
                'message' => 'Shift time overlaps with an existing staff assignment.',
            ], 409);
        }
    
       
    
        $staffShift = StaffShift::create([
            'staff_id' => $request->staff_id,
            'shift_id' => $request->shift_id,
            'date' => $request->date,
            'start_time' => $startTime,
            'end_time' => $endTime,
        ]);
    
 

        return response()->json($staffShift, 201);
    }
    

    public function update(Request $request, $id)
    {
        $request->validate([
            'staff_id' => 'sometimes|exists:staff,id',
            'shift_id' => 'sometimes|exists:shifts,id',
            'date' => 'sometimes|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
        ]);

        $staffShift = StaffShift::findOrFail($id);

        $staffId = $request->staff_id ?? $staffShift->staff_id;
        $shiftId = $request->shift_id ?? $staffShift->shift_id;
        $date = $request->date ?? $staffShift->date;

        $shift = Shift::findOrFail($shiftId);
        $startTime = $request->start_time ?? $shift->start_time;
        $endTime = $request->end_time ?? $shift->end_time;

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
        ]);

        return response()->json($staffShift, 200);
    }

    public function destroy($id)
    {
        $staffShift = StaffShift::findOrFail($id);
        $staffShift->delete();
        return response()->json(null, 204);
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
        $query = StaffShift::where('staff_id', $staffId)
            ->where('date', $date)
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
                });
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
