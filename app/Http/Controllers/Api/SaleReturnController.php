<?php

namespace App\Http\Controllers\Api;

use App\Models\SaleReturn;
use App\Models\Product;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleReturnController extends Controller
{
    public function getAllSaleReturns()
    {
        $returns = SaleReturn::with(['sale.customer', 'product'])->get();
        return response()->json(['success' => 1, 'data' => $returns], 200);
    }

    public function viewSaleReturn($id)
    {
        $return = SaleReturn::with(['sale.customer', 'product'])->find($id);
        if (!$return) {
            return response()->json(['success' => 0, 'message' => 'Sale return not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Sale return retrieved successfully', 'data' => $return], 200);
    }

    public function createSaleReturn(Request $request)
    {
        $request->validate([
            'sale_id'       => 'required|exists:sales,id',
            'product_id'    => 'required|exists:products,id',
            'quantity'      => 'required|integer|min:1',
            'return_amount' => 'required|numeric|min:0',
            'return_date'   => 'required|date',
            'reason'        => 'required|string',
            'status'        => 'required|in:Pending,Processing,Completed',
        ]);

        DB::beginTransaction();
        try {
            $saleReturn = SaleReturn::create([
                'reference'     => 'SR-' . str_pad(SaleReturn::count() + 1, 3, '0', STR_PAD_LEFT),
                'sale_id'       => $request->sale_id,
                'product_id'    => $request->product_id,
                'quantity'      => $request->quantity,
                'return_amount' => $request->return_amount,
                'return_date'   => $request->return_date,
                'reason'        => $request->reason,
                'status'        => $request->status,
            ]);

            // Restore stock when return is Completed
            if ($request->status === 'Completed') {
                Product::where('id', $request->product_id)->increment('quantity', $request->quantity);
            }

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Sale return created successfully', 'data' => $saleReturn->load(['sale.customer', 'product'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to create sale return', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSaleReturn(Request $request, $id)
    {
        $saleReturn = SaleReturn::find($id);
        if (!$saleReturn) {
            return response()->json(['success' => 0, 'message' => 'Sale return not found'], 404);
        }

        $request->validate([
            'sale_id'       => 'required|exists:sales,id',
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
            if ($saleReturn->status === 'Completed') {
                Product::where('id', $saleReturn->product_id)->decrement('quantity', $saleReturn->quantity);
            }

            $saleReturn->update($request->only([
                'sale_id', 'product_id', 'quantity',
                'return_amount', 'return_date', 'reason', 'status',
            ]));

            // Apply new stock effect if now Completed
            if ($request->status === 'Completed') {
                Product::where('id', $request->product_id)->increment('quantity', $request->quantity);
            }

            DB::commit();
            return response()->json(['success' => 1, 'message' => 'Sale return updated successfully', 'data' => $saleReturn->load(['sale.customer', 'product'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to update sale return', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteSaleReturn($id)
    {
        $saleReturn = SaleReturn::find($id);
        if (!$saleReturn) {
            return response()->json(['success' => 0, 'message' => 'Sale return not found'], 404);
        }

        // Reverse stock if was Completed
        if ($saleReturn->status === 'Completed') {
            Product::where('id', $saleReturn->product_id)->decrement('quantity', $saleReturn->quantity);
        }

        $saleReturn->delete();

        return response()->json(['success' => 1, 'message' => 'Sale return deleted successfully'], 200);
    }
}
