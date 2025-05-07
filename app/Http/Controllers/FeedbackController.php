<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'message' => 'required|string', // Feedback message is required
            'temp_id' => 'nullable|string|max:255', // Temp ID for unregistered users
        ]);

        // Determine if the user is registered or not
        $customerId = auth()->check() ? auth()->id() : null;
        $tempId = $customerId ? null : ($validatedData['temp_id'] ?? 'Guest-' . uniqid());

        // Store feedback in the database
        $feedback = Feedback::create([
            'customer_id' => $customerId,
            'temp_id' => $tempId,
            'message' => $validatedData['message'],
            'created_at' => now(),
        ]);

        // Prepare the response
        $response = [
            'message' => 'Feedback submitted successfully.',
            'feedback' => [
                'customer_name' => $customerId ? auth()->user()->name : $tempId,
                'profile_image' => $customerId ? auth()->user()->profile_image : 'default-profile.png',
                'feedback_message' => $feedback->message,
                'created_at' => $feedback->created_at,
            ],
        ];

        return response()->json($response, 201);
    }

    // Get all feedback
    public function index()
    {
        $feedback = Feedback::with('customer:id,name,profile_image') // Include customer details
        ->get()
        ->map(function ($item) {
            return [
                'customer_name' => $item->customer ? $item->customer->name : $item->temp_id,
                'profile_image' => $item->customer ? $item->customer->profile_image : 'default-profile.png',
                'feedback_message' => $item->message,
                'created_at' => $item->created_at,
            ];
        });

    // Return the feedback as a JSON response
    return response()->json($feedback, 200);
    }
}
