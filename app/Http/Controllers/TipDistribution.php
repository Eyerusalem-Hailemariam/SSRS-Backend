<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\TipDistributions;
use App\Models\Attendance;
use App\Models\Order;

class TipDistribution extends Controller
{
public function distributeTipsToCheffs($orderId)
{
    $payment = Payment::where('order_id', $orderId)
                      ->where('status', 'completed')
                      ->first();

    $order = Order::find($orderId);

    if (!$payment || !$order) {
        return response()->json(['error' => 'Payment or order not found'], 404);
    }

    $tips = $payment->amount - $order->total_price;

    if ($tips <= 0) {
        return response()->json(['error' => 'No tips to distribute (payment equals or less than order total)'], 200);
    }

    $alreadyDistributed = TipDistributions::where('payment_id', $payment->id)->exists();
    if ($alreadyDistributed) {
        return response()->json(['message' => 'Tips already distributed for this payment.'], 200);
    }

    $paymentTime = $payment->created_at;

    $clockIns = Attendance::where('mode', 'clock_in')
        ->where('status', 'present')
        ->where('scanned_at', '<=', $paymentTime)
        ->get();

   $presentCheffs = [];

foreach ($clockIns as $clockIn) {
    $clockOut = Attendance::where('staff_id', $clockIn->staff_id)
        ->where('mode', 'clock_out')
        ->where('status', 'present')
        ->where('scanned_at', '>=', $paymentTime)
        ->first();

    $isStillWorking = !$clockOut;

    $cheff = Staff::where('id', $clockIn->staff_id)
        ->where('role', 'cheff')
        ->first();

    if ($cheff && ($clockOut || $isStillWorking)) {
        $presentCheffs[] = $cheff;
    }
}


    if (empty($presentCheffs)) {
        return response()->json(['message' => 'No cheffs were present at the time of payment.'], 200);
    }

    $tipPerCheff = $tips / count($presentCheffs);
    $distributedTo = [];

    foreach ($presentCheffs as $cheff) {
        $cheff->tips += $tipPerCheff;
        $cheff->save();

        $distribution = TipDistributions::where('staff_id', $cheff->id)->first();

        if ($distribution) {
            $distribution->amount += $tipPerCheff;
            $distribution->save();
        } else {
            TipDistributions::create([
                'staff_id' => $cheff->id,
                'amount' => $tipPerCheff,
                'payment_id' => $payment->id,
            ]);
        }

        $distributedTo[] = [
            'staff_id' => $cheff->id,
            'name' => $cheff->name,
            'distributed_tip' => $tipPerCheff
        ];
    }

    return response()->json([
        'total_tips' => $tips,
        'tip_per_cheff' => $tipPerCheff,
        'distributed_to' => $distributedTo
    ]);
}

}
