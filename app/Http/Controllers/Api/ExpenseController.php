<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function getAllExpenses()
    {
        $expenses = Expense::with(['category', 'store'])->get();
        return response()->json(['success' => 1, 'data' => $expenses], 200);
    }

    public function viewExpense($id)
    {
        $expense = Expense::with(['category', 'store'])->find($id);
        if (!$expense) {
            return response()->json(['success' => 0, 'message' => 'Expense not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Expense retrieved successfully', 'data' => $expense], 200);
    }

    public function createExpense(Request $request)
    {
        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'store_id'            => 'nullable|exists:stores,id',
            'date'                => 'required|date',
            'amount'              => 'required|numeric|min:0',
            'payment_method'      => 'required|in:cash,card,bank',
            'note'                => 'nullable|string',
            'attachment'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('uploads/expenses', 'public');
        }

        $expense = Expense::create([
            'reference'           => 'EXP-' . str_pad(Expense::count() + 1, 3, '0', STR_PAD_LEFT),
            'expense_category_id' => $request->expense_category_id,
            'store_id'            => $request->store_id,
            'date'                => $request->date,
            'amount'              => $request->amount,
            'payment_method'      => $request->payment_method,
            'note'                => $request->note,
            'attachment'          => $attachmentPath,
        ]);

        ActivityLogService::log('Add', 'Expenses', "Added expense {$expense->reference}");
        return response()->json(['success' => 1, 'message' => 'Expense created successfully', 'data' => $expense->load(['category', 'store'])], 201);
    }

    public function updateExpense(Request $request, $id)
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json(['success' => 0, 'message' => 'Expense not found'], 404);
        }

        $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'store_id'            => 'nullable|exists:stores,id',
            'date'                => 'required|date',
            'amount'              => 'required|numeric|min:0',
            'payment_method'      => 'required|in:cash,card,bank',
            'note'                => 'nullable|string',
            'attachment'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $expense->update($request->only([
            'expense_category_id', 'store_id', 'date',
            'amount', 'payment_method', 'note',
        ]));

        if ($request->hasFile('attachment')) {
            $expense->attachment = $request->file('attachment')->store('uploads/expenses', 'public');
            $expense->save();
        }

        ActivityLogService::log('Edit', 'Expenses', "Updated expense {$expense->reference}");
        return response()->json(['success' => 1, 'message' => 'Expense updated successfully', 'data' => $expense->load(['category', 'store'])], 200);
    }

    public function deleteExpense($id)
    {
        $expense = Expense::find($id);
        if (!$expense) {
            return response()->json(['success' => 0, 'message' => 'Expense not found'], 404);
        }

        $ref = $expense->reference;
        $expense->delete();
        ActivityLogService::log('Delete', 'Expenses', "Deleted expense {$ref}");
        return response()->json(['success' => 1, 'message' => 'Expense deleted successfully'], 200);
    }
}
