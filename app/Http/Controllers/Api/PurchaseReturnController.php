<?php

namespace App\Http\Controllers\Api;

use App\Models\PurchaseReturn;
use App\Models\Purchase;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseReturnController extends Controller
{
    public function getAllPurchaseReturns()
    {
        $returns = PurchaseReturn::with(['purchase.supplier', 'product'])->get();
        return response()->json(['success' => 1, 'data' => $returns], 200);
    }

    public function viewPurchaseReturn($id)
    {
        $return = PurchaseReturn::with(['purchase.supplier', 'product'])->find($id);
        if (!$return) {
            return response()->json(['success' => 0, 'message' => 'Purchase return not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Purchase return retrieved successfully', 'data' => $return], 200);
    }

    public function createPurchaseReturn(Request $request)
    {
        $request->validate([
            'purchase_id'   => 'required|exists:purchases,id',
            'product_id'    => 'required|exists:products,id',
            'quantity'      => 'required|integer|min:1',
            'return_amount' => 'required|numeric|min:0',
            'return_date'   => 'required|date',
            'reason'        => 'required|string',
            'status'        => 'required|in:Pending,Processing,Completed',
        ]);

        DB::beginTransaction();
        try {
            $purchaseReturn = PurchaseReturn::create([
                'reference'     => 'PR-' . str_pad(PurchaseReturn::count() + 1, 3, '0', STR_PAD_LEFT),
                'purchase_id'   => $request->purchase_id,
                'product_id'    => $request->product_id,
                'quantity'      => $request->quantity,
                'return_amount' => $request->return_amount,
                'return_date'   => $request->return_date,
                'reason'        => $request->reason,
                'status'        => $request->status,
            ]);

            // Decrease stock when return is completed
            if ($request->status === 'Completed') {
                Product::where('id', $request->product_id)->decrement('quantity', $request->quantity);
            }

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Purchase return created successfully', 'data' => $purchaseReturn->load(['purchase.supplier', 'product'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to create purchase return', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePurchaseReturn(Request $request, $id)
    {
        $purchaseReturn = PurchaseReturn::find($id);
        if (!$purchaseReturn) {
            return response()->json(['success' => 0, 'message' => 'Purchase return not found'], 404);
        }

        $request->validate([
            'purchase_id'   => 'required|exists:purchases,id',
            'product_id'    => 'required|exists:products,id',
            'quantity'      => 'required|integer|min:1',
            'return_amount' => 'required|numeric|min:0',
            'return_date'   => 'required|date',
            'reason'        => 'required|string',
            'status'        => 'required|in:Pending,Processing,Completed',
        ]);

        DB::beginTransaction();
        try {
            // Reverse old stock effect if was Completed
            if ($purchaseReturn->status === 'Completed') {
                Product::where('id', $purchaseReturn->product_id)->increment('quantity', $purchaseReturn->quantity);
            }

            $purchaseReturn->update($request->only([
                'purchase_id', 'product_id', 'quantity',
                'return_amount', 'return_date', 'reason', 'status',
            ]));

            // Apply new stock effect if now Completed
            if ($request->status === 'Completed') {
                Product::where('id', $request->product_id)->decrement('quantity', $request->quantity);
            }

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Purchase return updated successfully', 'data' => $purchaseReturn->load(['purchase.supplier', 'product'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to update purchase return', 'error' => $e->getMessage()], 500);
        }
    }

    public function deletePurchaseReturn($id)
    {
        $purchaseReturn = PurchaseReturn::find($id);
        if (!$purchaseReturn) {
            return response()->json(['success' => 0, 'message' => 'Purchase return not found'], 404);
        }

        // Reverse stock if was Completed
        if ($purchaseReturn->status === 'Completed') {
            Product::where('id', $purchaseReturn->product_id)->increment('quantity', $purchaseReturn->quantity);
        }

        $purchaseReturn->delete();

        return response()->json(['success' => 1, 'message' => 'Purchase return deleted successfully'], 200);
    }
}
