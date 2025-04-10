<?php

namespace App\Http\Controllers;

use App\Models\MenuIngredient;
use App\Models\Ingredient;
use Illuminate\Http\Request;

class MenuIngredientController extends Controller
{
    // Get all menu ingredients
    public function index()
    {
        return response()->json(MenuIngredient::with('ingredients')->get(), 200);
    }

    // Store a new menu ingredient
    public function store(Request $request, $menuItemId)
    {
        $validatedData = $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric',
            'unit' => 'required|string|max:50',
        ]);

        $menuIngredient = MenuIngredient::findOrFail($menuItemId);
        $menuIngredient->ingredients()->attach($validatedData['ingredient_id'], [
            'quantity' => $validatedData['quantity'],
            'unit' => $validatedData['unit'],
        ]);

        return response()->json(['message' => 'Menu ingredient added successfully.'], 201);
    }

    // Update a menu ingredient
    public function update(Request $request, $menuItemId, $ingredientId)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|numeric',
            'unit' => 'required|string|max:50',
        ]);

        $menuIngredient = MenuIngredient::findOrFail($menuItemId);
        $menuIngredient->ingredients()->updateExistingPivot($ingredientId, [
            'quantity' => $validatedData['quantity'],
            'unit' => $validatedData['unit'],
        ]);

        return response()->json(['message' => 'Menu ingredient updated successfully.'], 200);
    }

    // Delete a menu ingredient
    public function destroy($menuItemId, $ingredientId)
    {
        $menuIngredient = MenuIngredient::findOrFail($menuItemId);
        $menuIngredient->ingredients()->detach($ingredientId);

        return response()->json(['message' => 'Menu ingredient deleted successfully.'], 200);
    }
}
