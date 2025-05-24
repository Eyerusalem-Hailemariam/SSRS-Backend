<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\TipDistributions;
use App\Models\Attendance;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class TipDistribution extends Controller
{
public function distributeTipsToCheffs($orderId)
{
    // Step 1: Get order and payment
    $order = Order::find($orderId);
    if (!$order) {
        return response()->json(['message' => 'Order not found.'], 404);
    }

    $payment = Payment::where('order_id', $orderId)
        ->where('status', 'completed')
        ->first();

    if (!$payment) {
        return response()->json(['message' => 'No completed payment found for this order.'], 404);
    }

    $paymentTime = $payment->created_at;
    $tipAmount = $payment->amount - $order->total_price;

    if ($tipAmount <= 0) {
        return response()->json(['message' => 'No tips to distribute.']);
    }

    // Step 2: Get cheffs who had a shift that includes the payment time
    $cheffShifts = DB::table('staff_shifts')
        ->where('date', $paymentTime->toDateString())
        ->whereTime('start_time', '<=', $paymentTime->toTimeString())
        ->whereTime('end_time', '>=', $paymentTime->toTimeString())
        ->pluck('staff_id');

    $eligibleCheffs = [];

    foreach ($cheffShifts as $staffId) {
        // Check for clock-in before payment time
        $clockIn = Attendance::where('staff_id', $staffId)
            ->where('mode', 'clock_in')
            ->where('status', 'present')
            ->where('scanned_at', '<=', $paymentTime)
            ->orderByDesc('scanned_at')
            ->first();

        if (!$clockIn) continue;

        // Ensure no clock-out after that clock-in but before payment
        $hasClockedOut = Attendance::where('staff_id', $staffId)
            ->where('mode', 'clock_out')
            ->where('status', 'present')
            ->where('scanned_at', '>=', $clockIn->scanned_at)
            ->where('scanned_at', '<', $paymentTime)
            ->exists();

        if ($hasClockedOut) continue;

        // Confirm the staff is a cheff
        $cheff = Staff::where('id', $staffId)->where('role', 'cheff')->first();
        if ($cheff) {
            $eligibleCheffs[] = $cheff;
        }
    }

    // Step 3: Handle if no cheffs were found
    if (count($eligibleCheffs) === 0) {
        return response()->json(['message' => 'No cheffs were present at the time of payment.']);
    }

    // Step 4: Distribute the tips
    $tipPerCheff = $tipAmount / count($eligibleCheffs);
    foreach ($eligibleCheffs as $cheff) {
        $cheff->tips += $tipPerCheff;
        $cheff->save();
    }

    return response()->json([
        'message' => 'Tips distributed successfully.',
        'tip_per_cheff' => $tipPerCheff,
        'total_cheffs' => count($eligibleCheffs),
        'cheffs' => $eligibleCheffs
    ]);
}




}
