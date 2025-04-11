<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;

class ChapaController extends Controller
{
    public function initializePayment(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric',
            'email' => 'required|email',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'phone_number' => 'required|string',
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
            
                // Update order payment status
                Order::whereHas('payment', function ($query) use ($reference) {
                    $query->where('tx_ref', $reference);
                })->update(['payment_status' => 'paid']);
            
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
}


