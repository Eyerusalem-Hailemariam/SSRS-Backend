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
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
        ]);

        // Check if there is an existing shift that overlaps with the new shift's time
        $existingShift = Shift::where('start_time', '<', $request->end_time)
            ->where('end_time', '>', $request->start_time)
            ->first();

        if ($existingShift) {
            return response()->json([
                'message' => 'The shift times overlap with an existing shift.'
            ], 409); // Conflict response
        }

        // If no conflict, create the new shift
        $shift = Shift::create($request->all());

        

        return response()->json($shift, 201);
    }

    public function update(Request $request, $id)
    {
        // Validate only the name
        $request->validate([
            'name' => 'required|string|max:255',
        ]);
    
        // Find the shift
        $shift = Shift::findOrFail($id);
    
        // Check if the name is the same
        if ($request->name === $shift->name) {
            return response()->json([
                'message' => 'The name is already up to date.',
            ], 200);
        }
    
        // Update the name
        $shift->update([
            'name' => $request->name,
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
