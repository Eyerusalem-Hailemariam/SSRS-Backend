<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\StaffShift;
use Carbon\Carbon;
use App\Models\TipDistributions;
use DB;
use App\Models\Payroll;

class PayrollController extends Controller
{
   
    protected function getTaxRate($income)
    {
        if ($income >= 650 && $income <= 1650) {
            return 0.10;
        } elseif ($income >= 1651 && $income <= 3200) {
            return 0.15;
        } elseif ($income >= 3201 && $income <= 5250) {
            return 0.20;
        } elseif ($income >= 5251 && $income <= 7800) {
            return 0.25;
        } elseif ($income >= 7801 && $income <= 10900) {
            return 0.30;
        } elseif ($income > 10900) {
            return 0.35;
        }

        return 0;
    }

    protected function getOvertimeMultiplier($overtime_type)
    {
        switch ($overtime_type) {
            case 'holiday':
                return 2.5;
            case 'weekend':
                return 2.0; 
            case 'night':
                return 1.5; 
            default:
                return 1.25; 
        }
    }
    public function calculatePayrollForAll(Request $request)
    {
    $request->validate([
        'start_date' => 'required|date',
        'end_date' => 'required|date|after_or_equal:start_date',
    ]);

    $start_date = Carbon::parse($request->start_date)->startOfDay();
    $end_date = Carbon::parse($request->end_date)->endOfDay();

    $all_staffs = Staff::all();
    $payrolls = [];

    foreach ($all_staffs as $staff) {
        $total_salary = $staff->total_salary;
        $tips = TipDistributions::where('staff_id', $staff->id)
        ->whereBetween('created_at', [$start_date, $end_date])
        ->sum('amount');

        $shifts = StaffShift::where('staff_id', $staff->id)
            ->whereBetween('start_time', [$start_date, $end_date])
            ->get();

        $assigned_days = $shifts->where('is_overtime', 0)
            ->groupBy(function ($shift) {
                return Carbon::parse($shift->start_time)->toDateString();
            })->count();

        if ($assigned_days == 0) {
            continue;
        }

        $daily_salary = $total_salary / $assigned_days;
        $daily_hours = 8; 
        $hourly_rate = $daily_salary / $daily_hours;
        $minute_rate = $hourly_rate / 60;

        $normal_earned = 0;
        $overtime_earned = 0;

        $grouped_shifts_by_day = $shifts->groupBy(function ($shift) {
            return Carbon::parse($shift->start_time)->toDateString();
        });

        foreach ($grouped_shifts_by_day as $day => $shifts_of_day) {
            $total_late_minutes = 0;
            $total_early_minutes = 0;
            $total_deduct_minutes = 0;
            $skip_day = false;

            foreach ($shifts_of_day as $shift) {
                $attendances = Attendance::where('staff_shift_id', $shift->id)->get();

                $was_absent = $attendances->contains(function ($attendance) {
                    return $attendance->status == 'absent';
                });

                $has_admin_approved_absence = $attendances->contains(function ($attendance) {
                    return $attendance->status == 'absent' && $attendance->approved_by_admin == 1;
                });

                if (!$shift->is_overtime && $was_absent && !$has_admin_approved_absence) {
                    $skip_day = true;
                    break;
                }

                if (!$shift->is_overtime && !$has_admin_approved_absence) {
                    $late_minutes = $attendances->sum(function ($attendance) {
                        return ($attendance->late_approved == 1) ? 0 : $attendance->late_minutes;
                    });

                    $early_minutes = $attendances->sum(function ($attendance) {
                        return ($attendance->early_approved == 1) ? 0 : $attendance->early_minutes;
                    });

                    $total_late_minutes += $late_minutes;
                    $total_early_minutes += $early_minutes;
                }
            }

            if ($skip_day) {
                continue;
            }

         
            $total_deduct_minutes = $total_late_minutes + $total_early_minutes;
            $daily_deduction = $total_deduct_minutes * $minute_rate;
            $net_daily_pay = max(0, $daily_salary - $daily_deduction);
            $normal_earned += $net_daily_pay;

            
         
        foreach ($shifts_of_day as $shift) {
            if (!$shift->is_overtime) {
                continue;
            }

            $attendances = Attendance::where('staff_shift_id', $shift->id)->get();

            $was_absent = $attendances->contains(function ($attendance) {
                return $attendance->status == 'absent';
            });

            if ($was_absent) {
                continue;
            }

            $late_minutes = $attendances->sum(function ($attendance) {
                return ($attendance->late_approved == 1) ? 0 : $attendance->late_minutes;
            });

            $early_minutes = $attendances->sum(function ($attendance) {
                return ($attendance->early_approved == 1) ? 0 : $attendance->early_minutes;
            });

            $deduct_minutes = $late_minutes + $early_minutes;
            $shift_start = Carbon::parse($shift->start_time);
            $shift_end = Carbon::parse($shift->end_time);

            if ($shift->is_night_shift && $shift_end->lessThan($shift_start)) {
                $shift_end->addDay(); 
        
            }

            if ($shift_start->greaterThan($shift_end)) {
                continue; 
            }


            $shift_duration_minutes = $shift_end->diffInMinutes($shift_start);


            $effective_minutes = max(0, $shift_duration_minutes - $deduct_minutes);

            $multiplier = $this->getOvertimeMultiplier($shift->overtime_type);
            $overtime_hourly_rate = $hourly_rate * $multiplier;
            $overtime_minute_rate = $overtime_hourly_rate / 60;
            $overtime_pay = $effective_minutes * $overtime_minute_rate;
            $overtime_earned += $overtime_pay;
        }
        }

        $total_earned = $normal_earned + $overtime_earned;
        $tax_rate = $this->getTaxRate($total_earned);
        $tax = $total_earned * $tax_rate;
        $net_salary = $total_earned - $tax;
        $net_salary_with_tips = $net_salary + $tips;

            Payroll::create([
        'staff_id' => $staff->id,
        'start_date' => $start_date->toDateString(),
        'end_date' => $end_date->toDateString(),
        'total_salary' => round($total_salary, 2),
        'assigned_days' => $assigned_days,
        'total_earned' => round($total_earned, 2),
        'tax' => round($tax, 2),
        'tips' => round($tips, 2),
        'net_salary_without_tips' => round($net_salary, 2),
        'net_salary_with_tips' => round($net_salary_with_tips, 2),
       
    ]);
         $staff->update(['tips' => 0]);

            $payrolls[] = [
        'staff' => $staff->name,
        'period' => [
            'start_date' => $start_date->toDateString(),
            'end_date' => $end_date->toDateString(),
        ],
        'total_salary' => round($total_salary, 2),
        'assigned_days' => $assigned_days,
        'daily_salary' => round($daily_salary, 2),
        'normal_earned' => round($normal_earned, 2),
        'overtime_earned' => round($overtime_earned, 2),
        'total_earned' => round($total_earned, 2),
        'tax' => round($tax, 2),
        'tips' => round($tips, 2),
        'net_salary_without_tips' => round($net_salary, 2),
        'net_salary_with_tips' => round($net_salary_with_tips, 2),
    ];

   

    }

    return response()->json([
        'payrolls' => $payrolls,
    ]);
}



}