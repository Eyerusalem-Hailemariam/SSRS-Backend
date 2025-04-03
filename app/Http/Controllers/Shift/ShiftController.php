<?php

namespace App\Http\Controllers\Shift;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\Staff;
use App\Notifications\ShiftUpdated;
/**
 * @OA\Tag(
 *     name="Shifts",
 *     description="Operations related to shift management"
 * )
 */

class ShiftController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

       /**
     * @OA\Get(
     *     path="/api/shifts",
     *     summary="Get all shifts for the authenticated staff member",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Shifts retrieved successfully",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="date", type="string", format="date"),
     *                 @OA\Property(property="start_time", type="string", format="time"),
     *                 @OA\Property(property="end_time", type="string", format="time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $staff = Auth::user();

        if (!$staff) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }


        $shifts = Shift::where('staff_id', $staff->id)->get()->map(function ($shift) {
            return [
                'id' => $shift->id,
                'date' => date("Y-m-d", strtotime($shift->start_time)), 
                'start_time' => date("H:i:s", strtotime($shift->start_time)),
                'end_time' => date("H:i:s", strtotime($shift->end_time)),
            ];
        });

        return response()->json(['shifts' => $shifts], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/shifts",
     *     summary="Create a new shift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"staff_id", "start_date", "end_date", "start_time", "end_time"},
     *             @OA\Property(property="staff_id", type="integer", example=1),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-04-10"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-04-12"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="time", example="17:00:00"),
     *             @OA\Property(property="is_overtime", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Shift created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Shift conflict detected"
     *     )
     * )
     */
    
    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
    
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date', 
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'is_overtime' => 'nullable|boolean',
        ]);
    
        $startDateTime = $request->start_date . ' ' . $request->start_time;
        $endDateTime = $request->end_date . ' ' . $request->end_time;

        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            return response()->json(['message' => 'End datetime must be after start datetime.'], 422);
        }
    
        if ($this->hasShiftConflict($request->staff_id, $startDateTime, $endDateTime)) {
            return response()->json(['message' => 'Shift conflict detected. Please choose different times.'], 409);
        }
    
        $shift = Shift::create([
            'staff_id' => $request->staff_id,
            'start_date' => $request->start_date,  
            'end_date' => $request->end_date,      
            'start_time' => $startDateTime,  
            'end_time' => $endDateTime, 
            'is_overtime' => $request->has('is_overtime') ? (bool) $request->is_overtime : false,
        ]);
        
        $staff = Staff::find($request->staff_id);
        if ($staff) {
            $staff->notify(new ShiftUpdated($shift, 'created'));
        }
    
        return response()->json($shift, 201);
    }
    
  /**
     * @OA\Put(
     *     path="/api/shifts/{id}",
     *     summary="Update an existing shift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shift ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"start_date", "end_date", "start_time", "end_time"},
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-04-10"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-04-12"),
     *             @OA\Property(property="start_time", type="string", format="time", example="09:00:00"),
     *             @OA\Property(property="end_time", type="string", format="time", example="17:00:00"),
     *             @OA\Property(property="is_overtime", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Shift")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Shift not found"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $shift = Shift::find($id);

        if (!$shift) {
            return response()->json(['message' => 'Shift not found'], 404);
        }

        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s',
            'is_overtime' => 'nullable|boolean',
        ]);

        $startDateTime = $request->start_date . ' ' . $request->start_time;
        $endDateTime = $request->end_date . ' ' . $request->end_time;

        if (strtotime($endDateTime) <= strtotime($startDateTime)) {
            return response()->json(['message' => 'End datetime must be after start datetime.'], 422);
        }

        if ($this->hasShiftConflict($shift->staff_id, $startDateTime, $endDateTime, $id)) {
            return response()->json(['message' => 'Shift conflict detected. Please choose different times.'], 409);
        }

        $shift->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'is_overtime' => $request->has('is_overtime') ? (bool) $request->is_overtime : false,
        ]);

        $staff = Staff::find($shift->staff_id);
        if ($staff) {
            $staff->notify(new ShiftUpdated($shift, 'updated'));
        }

        return response()->json(['message' => 'Shift updated successfully', 'shift' => $shift], 200);
    }


    /**
     * @OA\Delete(
     *     path="/api/shifts/{id}",
     *     summary="Delete a shift",
     *     tags={"Shifts"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Shift ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Shift deleted successfully"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Unauthorized"
     *     )
     * )
     */

    public function destroy($id)
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $shift = Shift::findOrFail($id);
        $shift->delete();

        return response()->json(['message' => 'Shift deleted successfully']);
    }

    
    private function hasShiftConflict($staffId, $startDateTime, $endDateTime, $excludeShiftId = null)
    {
        $query = Shift::where('staff_id', $staffId)
            ->where(function ($q) use ($startDateTime, $endDateTime) {
                $q->whereBetween('start_time', [$startDateTime, $endDateTime])
                  ->orWhereBetween('end_time', [$startDateTime, $endDateTime]);
            });
    
        if ($excludeShiftId) {
            $query->where('id', '!=', $excludeShiftId);
        }
    
        return $query->exists();
    }
    
}
