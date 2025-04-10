<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function index()
    {
        // Fetch all images
        $images = Image::all();

        // Return the images as a JSON response
        return response()->json($images);
    }

    public function store(Request $request)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|max:4096',
        ]);

        // Handle file upload
        $image = $request->file('image');
        $imagePath = $image->store('public/images');

        // Create a new image entry in the database
        $image = Image::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'path' => $imagePath,
        ]);

        // Return the created image as a JSON response with a success message
        return response()->json([
            'message' => 'Image created successfully.',
            'image' => $image
        ], 201); // HTTP status code 201 (Created)
    }

    public function show($id)
    {
        // Find the image by ID
        $image = Image::findOrFail($id);

        // Return the image as a JSON response
        return response()->json($image);
    }

    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:4096',
        ]);

        // Find the image by ID
        $image = Image::findOrFail($id);

        // If a new image file is uploaded, handle the update
        if ($request->hasFile('image')) {
            // Delete the old image file from storage
            Storage::delete($image->path);

            // Store the new image and update the path
            $imagePath = $request->file('image')->store('public/images');
            $image->path = $imagePath;
        }

        // Update other fields
        $image->title = $validatedData['title'];
        $image->description = $validatedData['description'];
        $image->save();

        // Return the updated image as a JSON response with a success message
        return response()->json([
            'message' => 'Image updated successfully.',
            'image' => $image
        ]);
    }

    public function destroy($id)
    {
        // Find the image by ID
        $image = Image::findOrFail($id);

        // Delete the image file from storage
        Storage::delete($image->path);

        // Delete the image record from the database
        $image->delete();

        // Return a success message as a JSON response
        return response()->json([
            'message' => 'Image deleted successfully.'
        ]);
    }
}
