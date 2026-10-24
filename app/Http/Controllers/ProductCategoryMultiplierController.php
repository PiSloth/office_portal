<?php

namespace App\Http\Controllers;

use App\Models\ProductCategoryMultiplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductCategoryMultiplierController extends Controller
{
    /**
     * Display a listing of category multipliers.
     */
    public function index(Request $request)
    {
        $categories = ProductCategoryMultiplier::withCount('promotionItems')
            ->orderBy('name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json($categories);
        }

        return back()->with('productCategories', $categories);
    }

    /**
     * Store a newly created category multiplier.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:product_category_multipliers,name'],
            'multiplier' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $category = ProductCategoryMultiplier::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' created successfully.",
                'category' => $category,
            ], 201);
        }

        return back()->with('success', "Category '{$category->name}' created successfully.");
    }

    /**
     * Update the specified category multiplier.
     */
    public function update(Request $request, ProductCategoryMultiplier $categoryMultiplier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('product_category_multipliers', 'name')->ignore($categoryMultiplier->id)],
            'multiplier' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $categoryMultiplier->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$categoryMultiplier->name}' updated successfully.",
                'category' => $categoryMultiplier,
            ]);
        }

        return back()->with('success', "Category '{$categoryMultiplier->name}' updated successfully.");
    }

    /**
     * Remove the specified category multiplier.
     */
    public function destroy(Request $request, ProductCategoryMultiplier $categoryMultiplier)
    {
        $name = $categoryMultiplier->name;
        $categoryMultiplier->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$name}' deleted successfully.",
            ]);
        }

        return back()->with('success', "Category '{$name}' deleted successfully.");
    }
}
