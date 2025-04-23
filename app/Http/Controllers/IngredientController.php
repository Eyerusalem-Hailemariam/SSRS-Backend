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
    // Validate the incoming request data
    $validatedData = $request->validate([
        'ingredients' => 'required|array', // Expect an array of ingredients
        'ingredients.*.name' => 'required|string|max:255', // Validate each ingredient's name
        'ingredients.*.calorie' => 'required|numeric|min:0', // Validate each ingredient's calorie
    ]);

    $createdIngredients = [];
    foreach ($validatedData['ingredients'] as $ingredientData) {
        // Create each ingredient and add it to the createdIngredients array
        $createdIngredients[] = Ingredient::create($ingredientData);
    }

    // Return the created ingredients as a JSON response with a success message
    return response()->json([
        'message' => 'Ingredients created successfully.',
        'ingredients' => $createdIngredients
    ], 201); // HTTP status code 201 means Created
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
