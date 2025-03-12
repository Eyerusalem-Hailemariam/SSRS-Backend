<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Staff;

class AttendanceController extends Controller
{
    
    //Admin selects Clock In or ClockOut mode and Scans staff IDs
    public function recordAttendance(Request $request) {
        $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'mode' => 'required|in:clock_in,clock_out'
        ]);

        $existingLog = Attendance::where('staff_id', $request->staff_id)
            ->whereDate('created_at', now()->toDateString())
            ->first();
    }
}