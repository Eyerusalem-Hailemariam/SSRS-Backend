<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Staff;


class ChapaController extends Controller
{

    /**
     * @OA\Post(
     *     path="/payment/chapa/initialize",
     *     summary="Initialize payment via Chapa",
     *     description="This endpoint initializes the Chapa payment gateway",
     *     tags={"Payment"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "email", "first_name", "last_name", "phone_number"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.0),
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="phone_number", type="string", example="1234567890")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment initialized successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="checkout_url", type="string", example="https://checkout.chapa.co/...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Failed to initialize payment",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="failed"),
     *             @OA\Property(property="message", type="string", example="Payment initialization failed")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object", additionalProperties={})
     *         )
     *     )
     * )
     */
    public function initializePayment(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number' => 'required|string',
            'order_id' => 'required|exists:orders,id',
        ]);

 
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $reference = 'CHAPA-' . uniqid();

            $data = [
                'amount' => $request->amount,
                'currency' => 'ETB',
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'tx_ref' => $reference,
                'callback_url' => route('callback.api', [$reference]),
                'return_url' => env('CHAPA_RETURN_URL'),
                'title' => 'Payment for Order',
                'description' => 'Payment transaction via Chapa',
            ];

            $chapaResponse = Http::withToken(env('CHAPA_SECRET_KEY'))
                ->post('https://api.chapa.co/v1/transaction/initialize', $data);

            
            Log::info('Chapa API Request:', [
                'url' => 'https://api.chapa.co/v1/transaction/initialize',
                'headers' => $chapaResponse->headers(),
                'body' => $chapaResponse->json(),
            ]);
            Log::info('Callback URL:', ['callback_url' => route('callback.api', [$reference])]);


            Payment::create([
                'tx_ref' => $reference,
                'amount' => $request->amount,
                'email' => $request->email,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number,
                'status' => 'pending',
                'order_id' => $request->order_id,
            ]);
            $responseData = $chapaResponse->json();

 
            if (isset($responseData['status']) && $responseData['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'checkout_url' => $responseData['data']['checkout_url'],
                    'tx_ref' => $reference
                ]);
            }

            return response()->json([
                'status' => 'failed',
                'message' => $responseData['message'] ?? 'Payment initialization failed'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/callback/{reference}",
     *     summary="Chapa payment callback",
     *     description="This endpoint handles the callback from Chapa after payment completion",
     *     tags={"Payment"},
     *     @OA\Parameter(
     *         name="reference",
     *         in="path",
     *         description="Payment reference",
     *         required=true,
     *         @OA\Schema(type="string", example="CHAPA-12345678")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment callback processed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Payment completed successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Payment failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="failed"),
     *             @OA\Property(property="message", type="string", example="Payment failed")
     *         )
     *     )
     * )
     */

    public function callback($reference)
    {
        try {
            Log::info('Received callback for reference: ' . $reference);

            $data = Http::withToken(env('CHAPA_SECRET_KEY'))
    ->get("https://api.chapa.co/v1/transaction/verify/{$reference}")
    ->json();


 
            Log::info('Chapa Callback Response:', $data);

 
            if (isset($data['status']) && $data['status'] == 'success') {
                Payment::where('tx_ref', $reference)->update(['status' => 'completed']);

                // Update the order status to 'paid'
                $order = Order::whereHas('payment', function ($query) use ($reference) {
                
                    $query->where('tx_ref', $reference);
                })->first();
    
                if ($order) {
                    $order->update(['payment_status' => 'paid']);
    
                    // Calculate tip if the amount paid exceeds the order total price
                    $payment = Payment::where('tx_ref', $reference)->first();
                    $tip = $payment->amount - $order->total_price;
    
                    if ($tip > 0) {
                        // Distribute the tip to chefs who are clocked in
                        $this->distributeTipToChefs($tip);
                    }
                }
    
                return response()->json(['status' => 'success', 'message' => 'Payment completed successfully']);
             } else {
                Payment::where('tx_ref', $reference)->update(['status' => 'failed']);
            
                return response()->json(['status' => 'failed', 'message' => 'Payment failed']);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }


private function distributeTipToChefs($tip)
{
    // Get the current time
    $currentTime = now();

    // Fetch chefs who are clocked in but not clocked out
    $chefs = Staff::where('role', 'chef')
        ->whereHas('attendance', function ($query) use ($currentTime) {
            $query->where('mode', 'clock_in')
                ->where('scanned_at', '<=', $currentTime)
                ->whereDoesntHave('attendance', function ($subQuery) {
                    $subQuery->where('mode', 'clock_out');
                });
        })
        ->get();

    if ($chefs->isEmpty()) {
        Log::info('No chefs are clocked in to distribute the tip.');
        return;
    }

    // Calculate the tip share for each chef
    $tipPerChef = $tip / $chefs->count();

    // Distribute the tip to each chef
    foreach ($chefs as $chef) {
        $chef->increment('tips', $tipPerChef);
        Log::info("Distributed {$tipPerChef} tip to Chef ID: {$chef->id}");
    }
}
}


