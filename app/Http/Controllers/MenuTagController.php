<?php

namespace App\Http\Controllers;

use App\Models\MenuTag;
use App\Models\Tag;
use Illuminate\Http\Request;

class MenuTagController extends Controller
{
    public function index()
    {
        $menuTags = MenuTag::all(); // Fetch all menu tags
        return response()->json($menuTags); // Return as JSON
    }

    public function store(Request $request)
    {
        // Validate incoming request
        $validatedData = $request->validate([
            'tag_id' => 'required|exists:tags,id', // Validate tag_id exists in tags table
            'menu_id' => 'required|exists:menu_tags,id', // Validate menu_id exists in menu_tags table
        ]);

        // Attach the tag to the menu model
        $menuTag = MenuTag::findOrFail($validatedData['menu_id']);
        $menuTag->tag()->attach($validatedData['tag_id']); // Attach tag to the menu tag

        // Return success message
        return response()->json([
            'message' => 'Menu tag added successfully.',
            'menuTag' => $menuTag
        ], 201); // HTTP status code 201 (Created)
    }

    public function destroy($menuId, $tagId)
    {
        // Find the menu tag model
        $menuTag = MenuTag::findOrFail($menuId);

        // Detach the tag from the menu tag
        $menuTag->tags()->detach($tagId);

        // Return success message
        return response()->json([
            'message' => 'Menu tag deleted successfully.'
        ]);
    }
}
