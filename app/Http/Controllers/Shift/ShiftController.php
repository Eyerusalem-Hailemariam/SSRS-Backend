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



        $shift = Shift::create($request->all());

        

        return response()->json($shift, 201);
    }

    public function update(Request $request, $id)
    {

        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $shift = Shift::findOrFail($id);
    
        if ($request->name === $shift->name) {
            return response()->json([
                'message' => 'The name is already up to date.',
            ], 200);
        }
    
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
