<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    // Get all menu items with related data
    public function index()
    {
        $menuItems = MenuItem::with(['ingredients', 'tags', 'images', 'category'])->get();
        return response()->json($menuItems);
    }

    // Get a single menu item by ID
    public function show($id)
    {
        $menuItem = MenuItem::with(['ingredients', 'tags', 'images'])->find($id);

        if (!$menuItem) {
            return response()->json(['error' => 'Menu item not found'], 404);
        }

        return response()->json($menuItem);
    }

    // Store a new menu item
    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',   // ✔ Validate category_id
            'image'       => 'nullable|string',                // (See note on images below)
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity'     => 'required|numeric|min:0'
        ]);

        $menuItem = MenuItem::create($validatedData);

        // Attach tags if provided
        if (!empty($validatedData['tags'])) {
            $menuItem->tags()->attach($validatedData['tags']);
        }

        // Attach menu ingredients if provided
        if (!empty($validatedData['menu_ingredients'])) {
            $menuItem->menuIngredients()->attach($validatedData['menu_ingredients']);
        }

        $totalCalories = $this->calculateTotalCalories($menuItem->menuIngredients);
        $menuItem->update(['total_calorie' => $totalCalories]);


        return response()->json(['message' => 'Menu item created successfully', 'menu_item' => $menuItem], 201);
    }

    // Update an existing menu item
    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['error' => 'Menu item not found'], 404);
        }

        $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',   // ✔ Validate category_id
            'image'       => 'nullable|string',                // (See note on images below)
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity'     => 'required|numeric|min:0'
        ]);

        $menuItem->update($validatedData);

        // Sync tags if provided
        if ($request->has('tags')) {
            $menuItem->tags()->sync($validatedData['tags'] ?? []);
        }

        // Sync menu ingredients if provided
        if ($request->has('menu_ingredients')) {
            $menuItem->menuIngredients()->sync($validatedData['menu_ingredients'] ?? []);
        }

        $totalCalories = $this->calculateTotalCalories($menuItem->menuIngredients);
        $menuItem->update(['total_calorie' => $totalCalories]);


        return response()->json(['message' => 'Menu item updated successfully', 'menu_item' => $menuItem]);
    }

    // Delete a menu item
    public function destroy($id)
    {
        $menuItem = MenuItem::find($id);

        if (!$menuItem) {
            return response()->json(['error' => 'Menu item not found'], 404);
        }

        // Detach related models before deletion
        $menuItem->tags()->detach();
        $menuItem->menuIngredients()->detach();

        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
    // Helper method to calculate total calories
    private function calculateTotalCalories($menuIngredients)
    {
        $totalCalories = 0;

        foreach ($menuIngredients as $menuIngredient) {
            $ingredient = $menuIngredient->ingredient; // Assuming menuIngredient has a relationship to Ingredient
            $totalCalories += $ingredient->calorie * $menuIngredient->quantity; // Multiply calorie by quantity
        }

        return $totalCalories;
    }
}

