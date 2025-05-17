<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Attendance;
use App\Models\StaffShift;
use Carbon\Carbon;
use DB;

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
                return 2.0;
            case 'weekend':
                return 1.5; 
            case 'night':
                return 1.25; 
            default:
                return 1.5; 
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
            $tips = $staff->tips ?? 0;

            $shifts = StaffShift::where('staff_id', $staff->id)
                ->whereBetween('start_time', [$start_date, $end_date])
                ->get();

            $assigned_days = $shifts->where('is_overtime', 0)->count();

            if ($assigned_days == 0) {
                continue;
            }

            $daily_salary = $total_salary / $assigned_days;
            $daily_hours = 24;
            $hourly_rate = $daily_salary / $daily_hours;
            $minute_rate = $hourly_rate / 60;

            $normal_earned = 0;
            $overtime_earned = 0;

            foreach ($shifts as $shift) {
                $shift_start = Carbon::parse($shift->start_time);
                $shift_end = Carbon::parse($shift->end_time);
                $shift_duration_minutes = $shift_end->diffInMinutes($shift_start);

                $attendances = Attendance::where('staff_shift_id', $shift->id)->get();


                $has_admin_approved_absence = $attendances->contains(function ($attendance) {
                    return $attendance->status == 'absent' && $attendance->approved_by_admin == 1;
                });

                if ($has_admin_approved_absence) {
                
                    $total_deduct_minutes = 0;
                } else {
                    $late_minutes = $attendances->sum(function ($attendance) {
                        return ($attendance->late_approved == 1) ? 0 : $attendance->late_minutes;
                    });

                    $early_minutes = $attendances->sum(function ($attendance) {
                        return ($attendance->early_approved == 1) ? 0 : $attendance->early_minutes;
                    });

                    $total_deduct_minutes = $late_minutes + $early_minutes;
                }

                if ($shift->is_overtime) {
                    $multiplier = $this->getOvertimeMultiplier($shift->overtime_type);
                    $overtime_hourly_rate = $hourly_rate * $multiplier;
                    $overtime_minute_rate = $overtime_hourly_rate / 60;
                    $overtime_pay = ($shift_duration_minutes * $overtime_minute_rate);
                    $overtime_deduction = $total_deduct_minutes * $overtime_minute_rate;
                    $net_overtime_pay = $overtime_pay - $overtime_deduction;
                    $overtime_earned += $net_overtime_pay;
                } else {
                    $shift_pay = $shift_duration_minutes * $minute_rate;
                    $deduction = $total_deduct_minutes * $minute_rate;
                    $net_shift_pay = $shift_pay - $deduction;
                    $daily_equivalent_pay = $net_shift_pay * ($daily_hours / ($shift_duration_minutes / 60));
                    $normal_earned += $daily_equivalent_pay;
                }
            }

            $total_earned = $normal_earned + $overtime_earned;
            $tax_rate = $this->getTaxRate($total_earned);
            $tax = $total_earned * $tax_rate;
            $net_salary = $total_earned - $tax;
            $net_salary_with_tips = $net_salary + $tips;

            $payrolls[] = [
                'staff' => $staff->name,
                'period' => [
                    'start_date' => $start_date->toDateString(),
                    'end_date' => $end_date->toDateString(),
                ],
                'total_salary' => round($total_salary, 2),
                'assigned_days' => $assigned_days,
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