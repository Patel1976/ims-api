<?php

namespace App\Http\Controllers\Api;

use App\Models\ExpenseCategory;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseCategoryController extends Controller
{
    public function getAllExpenseCategories()
    {
        $categories = ExpenseCategory::withCount('expenses')->get();
        return response()->json(['success' => 1, 'data' => $categories], 200);
    }

    public function viewExpenseCategory($id)
    {
        $category = ExpenseCategory::withCount('expenses')->find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Expense category not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Expense category retrieved successfully', 'data' => $category], 200);
    }

    public function createExpenseCategory(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|unique:expense_categories',
            'description' => 'nullable|string',
        ]);

        $category = ExpenseCategory::create($request->only(['name', 'description']));

        return response()->json(['success' => 1, 'message' => 'Expense category created successfully', 'data' => $category], 201);
    }

    public function updateExpenseCategory(Request $request, $id)
    {
        $category = ExpenseCategory::find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Expense category not found'], 404);
        }

        $request->validate([
            'name'        => ['required', 'string', Rule::unique('expense_categories')->ignore($id)],
            'description' => 'nullable|string',
        ]);

        $category->update($request->only(['name', 'description']));

        return response()->json(['success' => 1, 'message' => 'Expense category updated successfully', 'data' => $category], 200);
    }

    public function deleteExpenseCategory($id)
    {
        $category = ExpenseCategory::find($id);
        if (!$category) {
            return response()->json(['success' => 0, 'message' => 'Expense category not found'], 404);
        }

        if ($category->expenses()->exists()) {
            return response()->json(['success' => 0, 'message' => 'Cannot delete category with existing expenses'], 422);
        }

        $category->delete();

        return response()->json(['success' => 1, 'message' => 'Expense category deleted successfully'], 200);
    }
}
