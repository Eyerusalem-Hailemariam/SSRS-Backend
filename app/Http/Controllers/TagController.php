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
        'name' => 'required|string' // Expect a comma-separated string of tag names
    ]);

    // Split the comma-separated names into an array
    $tagNames = array_map('trim', explode(',', $request->name));

    $createdTags = [];
    $duplicateTags = [];

    foreach ($tagNames as $tagName) {
        try {
            // Attempt to create each tag
            $createdTags[] = Tag::create(['name' => $tagName]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Catch duplicate entry exception and add to duplicateTags array
            $duplicateTags[] = $tagName;
        }
    }

    // If there are duplicate tags, return an error message
    if (!empty($duplicateTags)) {
        return response()->json([
            'message' => 'Some tags already exist.',
            'duplicates' => $duplicateTags
        ], 400); // HTTP status code 400 means Bad Request
    }

    // Return the created tags as a JSON response with a success message
    return response()->json([
        'message' => 'Tags created successfully.',
        'tags' => $createdTags
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
            'name' => 'required|string'
            
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
