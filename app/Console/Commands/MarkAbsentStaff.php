<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StaffShift;
use App\Models\Attendance;
use Carbon\Carbon;

class MarkAbsentStaff extends Command
{
    protected $signature = 'attendance:mark-absent';
    protected $description = 'Mark staff as absent if they did not clock in';

    public function handle()
    {
        $today = Carbon::today();
        $shifts = StaffShift::whereDate('date', $today)->get();

        if ($shifts->isEmpty()) {
            $this->info('No shifts found for today.');
            return;
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
                    'mode' => 'clock_in',
                    'scanned_at' => now()->setTimezone('Africa/Nairobi'),
                    'status' => 'absent',
                    'is_late' => false,
                    'late_minutes' => 0,
                    'is_early' => false,
                    'early_minutes' => 0,
                ]);
            }
        }

        $this->info('Absent staff marked successfully.');
    }
}
