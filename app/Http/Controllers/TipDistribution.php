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
public function distributeTipsToChefs($orderId)
{
    
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

    
    $chefShifts = DB::table('staff_shifts')
        ->where('date', $paymentTime->toDateString())
        ->whereTime('start_time', '<=', $paymentTime->toTimeString())
        ->whereTime('end_time', '>=', $paymentTime->toTimeString())
        ->pluck('staff_id');

    $eligibleChefs = [];

    foreach ($chefShifts as $staffId) {
        
        $clockIn = Attendance::where('staff_id', $staffId)
            ->where('mode', 'clock_in')
            ->where('status', 'present')
            ->where('scanned_at', '<=', $paymentTime)
            ->orderByDesc('scanned_at')
            ->first();

        if (!$clockIn) continue;

       
        $hasClockedOut = Attendance::where('staff_id', $staffId)
            ->where('mode', 'clock_out')
            ->where('status', 'present')
            ->where('scanned_at', '>=', $clockIn->scanned_at)
            ->where('scanned_at', '<', $paymentTime)
            ->exists();

        if ($hasClockedOut) continue;

     
        $chef = Staff::where('id', $staffId)->where('role', 'chef')->first();
        if ($chef) {
            $eligibleChefs[] = $chef;
        }
    }


    if (count($eligibleChefs) === 0) {
        return response()->json(['message' => 'No chefs were present at the time of payment.']);
    }

    
    $tipPerChef = $tipAmount / count($eligibleChefs);
    foreach ($eligibleChefs as $chef) {
        $chef->tips += $tipPerChef;
        $chef->save();
    }

    return response()->json([
        'message' => 'Tips distributed successfully.',
        'tip_per_chef' => $tipPerChef,
        'total_chefs' => count($eligibleChefs),
        'chefs' => $eligibleChefs
    ]);
}




}
