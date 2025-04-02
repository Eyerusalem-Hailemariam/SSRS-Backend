<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::all(); // Fetch all tags
        return response()->json($tags); // Return the tags as a JSON response
    }

    public function store(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Create a new tag with the validated data
        $tag = Tag::create($request->all());

        // Return the created tag as a JSON response with a success message
        return response()->json([
            'message' => 'Tag created successfully.',
            'tag' => $tag
        ], 201); // HTTP status code 201 means Created
    }

    public function show(Tag $tag)
    {
        // Return a single tag as a JSON response
        return response()->json($tag);
    }

    public function update(Request $request, Tag $tag)
    {
        // Validate the incoming request data
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Update the tag with the validated data
        $tag->update($request->all());

        // Return the updated tag as a JSON response with a success message
        return response()->json([
            'message' => 'Tag updated successfully.',
            'tag' => $tag
        ]);
    }

    public function destroy(Tag $tag)
    {
        // Delete the tag
        $tag->delete();

        // Return a success message as a JSON response
        return response()->json([
            'message' => 'Tag deleted successfully.'
        ]);
    }
}
