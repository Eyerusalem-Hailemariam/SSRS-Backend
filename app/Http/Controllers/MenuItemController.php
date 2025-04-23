<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
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
        $menuItem = MenuItem::with(['ingredients', 'tags', 'images', 'category'])->find($id);

        if (!$menuItem) {
            return response()->json(['error' => 'Menu item not found'], 404);
        }

        return response()->json($menuItem);
    }

    // Store a new menu item
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image'       => 'nullable|file|image|max:8192',
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity'     => 'required|numeric|min:0'
        ]);

        if ($request->hasFile('image')) {
            $filename = uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('', $filename, 'public');
            $validatedData['image'] = $path;
        }

        $menuItem = MenuItem::create($validatedData);

        // Attach tags if provided
        if (!empty($validatedData['tags'])) {
            $menuItem->tags()->attach($validatedData['tags']);
        }

        // Attach menu ingredients if provided
        if (!empty($validatedData['ingredients'])) {
            foreach ($validatedData['ingredients'] as $ingredient) {
                $menuItem->ingredients()->attach($ingredient['ingredient_id'], [
                    'quantity' => $ingredient['quantity']
                ]);
            }
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

        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'price'       => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',   // ✔ Validate category_id
            'image'       => 'nullable|file|image|max:8192',
            'tags'        => 'nullable|array',
            'tags.*'      => 'exists:tags,id',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity'     => 'required|numeric|min:0'
        ]);

        // ✅ Only handle image if a new one was uploaded
        if ($request->hasFile('image')) {
            // Delete the old image file
            if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
                Storage::disk('public')->delete($menuItem->image);
            }

            // Store new image
            $filename = uniqid() . '.' . $request->file('image')->getClientOriginalExtension();
            $path = $request->file('image')->storeAs('', $filename, 'public');
            $validatedData['image'] = $path;
        }

        $menuItem->update($validatedData);

        // Sync tags if provided
        if ($request->has('tags')) {
            $menuItem->tags()->sync($validatedData['tags'] ?? []);
        }

        // Sync menu ingredients if provided
        if ($request->has('ingredients') && !empty($validatedData['ingredients'])) {
            $menuItem->menuIngredients()->delete();
            foreach ($validatedData['ingredients'] as $ingredient) {
                $menuItem->ingredients()->attach($ingredient['ingredient_id'], [
                    'quantity' => $ingredient['quantity']
                ]);
            }
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

        if ($menuItem->image && Storage::disk('public')->exists($menuItem->image)) {
            Storage::disk('public')->delete($menuItem->image);
        }

        // Detach related models before deletion
        $menuItem->tags()->detach();
        $menuItem->ingredients()->detach();

        $menuItem->delete();

        return response()->json(['message' => 'Menu item deleted successfully']);
    }
    
    // Helper method to calculate total calories
    private function calculateTotalCalories($menuIngredients)
    {
        $totalCalories = 0;

        if (empty($menuIngredients)) {
            return $totalCalories; // Return 0 if no ingredients are provided
        }

        foreach ($menuIngredients as $menuIngredient) {
            $ingredient = $menuIngredient->ingredient; // Assuming menuIngredient has a relationship to Ingredient
            $totalCalories += ($ingredient->calorie / 100) * $menuIngredient->quantity;
        }

        return round($totalCalories, 2);
    }
}

