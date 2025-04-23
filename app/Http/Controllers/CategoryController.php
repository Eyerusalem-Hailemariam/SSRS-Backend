<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all(); // Fetch all categories
        return response()->json($categories); // Return the categories as a JSON response
    }

    public function store(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        // Create a new category
        $category = Category::create($request->all());

        // Return the created category as a JSON response with a success message
        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category
        ], 201); // HTTP status code 201 (Created)
    }

    public function show(Category $category)
    {
        // Return a single category as a JSON response
        return response()->json($category);
    }

    public function update(Request $request, Category $category)
    {
        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255'  
        ]);

        // Update the category with the validated data
        $category->update($request->all());

        // Return the updated category as a JSON response with a success message
        return response()->json([
            'message' => 'Category updated successfully.',
            'category' => $category
        ]);
    }

    public function destroy(Category $category)
    {
        // Delete the category
        $category->delete();

        // Return a success message as a JSON response
        return response()->json([
            'message' => 'Category deleted successfully.'
        ]);
    }
}
