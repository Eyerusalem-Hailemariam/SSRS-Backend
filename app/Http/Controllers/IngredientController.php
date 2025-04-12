<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    // Get all ingredients
    public function index()
    {
        $ingredients = Ingredient::all();
        return response()->json(['ingredients' => $ingredients], 200);
    }

    // Store a new ingredient
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'calorie' => 'required|numeric|min:0', 
        ]);

        $ingredient = Ingredient::create($validatedData);
        return response()->json(['message' => 'Ingredient created successfully', 'ingredient' => $ingredient], 201);
    }

    // Show a specific ingredient
    public function show($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        return response()->json(['ingredient' => $ingredient], 200);
    }

    // Update an ingredient
    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'calorie' => 'required|numeric|min:0', 
           ]);

        $ingredient->update($validatedData);
        return response()->json(['message' => 'Ingredient updated successfully', 'ingredient' => $ingredient], 200);
    }

    // Delete an ingredient
    public function destroy($id)
    {
        $ingredient = Ingredient::findOrFail($id);
        $ingredient->delete();
        return response()->json(['message' => 'Ingredient deleted successfully'], 200);
    }
}
