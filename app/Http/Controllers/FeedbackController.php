<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    // store feedback for logged-in users
public function storeForLoggedInUser(Request $request)
{
    $validatedData = $request->validate([
        'message' => 'required|string', // Feedback message is required
    ]);

    // Get the logged-in user's ID and profile
    $customerId = auth()->id();
    $user = auth()->user();

    // Store feedback in the database
    $feedback = Feedback::create([
        'customer_id' => $customerId,
        'temp_id' => null,
        'message' => $validatedData['message'],
        'created_at' => now(),
    ]);

    // Prepare the response
    $response = [
        'message' => 'Feedback submitted successfully.',
        'feedback' => [
            'feedback_id' => $feedback->id, 
            'customer_name' => $user->name,
            'feedback_message' => $feedback->message,
            'created_at' => $feedback->created_at,
        ],
    ];

    return response()->json($response, 201);
}

// store feedback for guest users
public function storeForGuestUser(Request $request)
{
    $validatedData = $request->validate([
        'message' => 'required|string', // Feedback message is required
        'temp_id' => 'nullable|string|max:255', // Temp ID for unregistered users
    ]);

    // Generate a temp ID if not provided
    $tempId = $validatedData['temp_id'] ?? 'Guest-' . uniqid();

    // Store feedback in the database
    $feedback = Feedback::create([
        'customer_id' => null,
        'temp_id' => $tempId,
        'message' => $validatedData['message'],
        'created_at' => now(),
    ]);

    // Prepare the response
    $response = [
        'message' => 'Feedback submitted successfully.',
        'feedback' => [
            'feedback_id' => $feedback->id, 
            'customer_name' => $tempId,
            'feedback_message' => $feedback->message,
            'created_at' => $feedback->created_at,
        ],
    ];

    return response()->json($response, 201);
}

    // Get all feedback
    public function index()
    {
        $feedback = Feedback::with('customer:id,name') // Include customer details
        ->get()
        ->map(function ($item) {
            return [
                'customer_name' => $item->customer ? $item->customer->name : $item->temp_id,
                'feedback_message' => $item->message,
                'created_at' => $item->created_at,
            ];
        });

    // Return the feedback as a JSON response
    return response()->json($feedback, 200);
    }
}
