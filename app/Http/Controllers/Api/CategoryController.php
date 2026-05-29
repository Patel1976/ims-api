<?php

namespace App\Http\Controllers\Api;

use App\Models\Category;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function getAllCategories()
    {
        $categories = Category::withCount('products')->get()->map(function ($cat) {
            $cat->products = $cat->products_count;
            return $cat;
        });
        return response()->json(['success' => 1, 'data' => $categories], 200);
    }

    public function createCategory(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'slug'        => 'required|string|unique:categories',
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $category = Category::create($request->only(['name', 'slug', 'description', 'status']));

        return response()->json(['success' => 1, 'message' => 'Category created successfully', 'data' => $category], 201);
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Category not found'], 404);
        }

        $request->validate([
            'name'        => 'required|string',
            'slug'        => ['required', 'string', Rule::unique('categories')->ignore($id)],
            'description' => 'nullable|string',
            'status'      => 'required|in:Active,Inactive',
        ]);

        $category->update($request->only(['name', 'slug', 'description', 'status']));

        return response()->json(['success' => 1, 'message' => 'Category updated successfully', 'data' => $category], 200);
    }

    public function viewCategory($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Category not found'], 404);
        }

        return response()->json(['success' => 1, 'message' => 'Category retrieved successfully', 'data' => $category], 200);
    }

    public function deleteCategory($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['success' => 1, 'message' => 'Category deleted successfully'], 200);
    }
}
