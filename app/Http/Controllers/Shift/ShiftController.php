<?php

namespace App\Http\Controllers\Shift;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use Carbon\Carbon;

class ShiftController extends Controller
{
public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|max:255',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i',
        'is_overtime' => 'nullable|boolean',
        'overtime_type' => 'nullable|in:normal,weekly,holiday,weekend',
    ]);

    $isOvertime = $request->boolean('is_overtime');

    // Ensure overtime_type only if is_overtime is true
    if ($isOvertime && !$request->filled('overtime_type')) {
        return response()->json(['error' => 'Overtime type is required when is_overtime is true.'], 422);
    }

    $shift = Shift::create([
        'name' => $request->name,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'is_overtime' => $isOvertime,
        'overtime_type' => $isOvertime ? $request->overtime_type : null,
    ]);

    return response()->json($shift, 201);
}



    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required|string|max:255',
            'is_overtime' => 'boolean',
        ]);

        $shift = Shift::findOrFail($id);
    
        if ($request->name === $shift->name) {
            return response()->json([
                'message' => 'The name is already up to date.',
            ], 200);
        }
        if($shift->is_overtime && $request->is_overtime === false) {
            return response()->json([
                'message' => 'Cannot change overtime status to false.',
            ], 409);
        }
    
        $shift->update([
            'name' => $request->name,
            'is_overtime' => $request->is_overtime,
        ]);
    
        return response()->json([
            'message' => 'Shift name updated successfully.',
            'shift' => $shift,
        ], 200);
    }
    
    
    public function index()
    {
        $shifts = Shift::all();
        return response()->json($shifts, 200);
    }
    
    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);
        $shift->delete();
        return response()->json(null, 204);
    }



}
