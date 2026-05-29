<?php

namespace App\Http\Controllers\Api;

use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function getAllAdjustments()
    {
        $adjustments = StockAdjustment::with(['store', 'items.product'])->get();
        return response()->json(['success' => 1, 'data' => $adjustments], 200);
    }

    public function viewAdjustment($id)
    {
        $adjustment = StockAdjustment::with(['store', 'items.product'])->find($id);
        if (!$adjustment) {
            return response()->json(['success' => 0, 'message' => 'Stock adjustment not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Stock adjustment retrieved successfully', 'data' => $adjustment], 200);
    }

    public function createAdjustment(Request $request)
    {
        $request->validate([
            'store_id'           => 'required|exists:stores,id',
            'date'               => 'required|date',
            'type'               => 'required|in:addition,subtraction',
            'reason'             => 'required|in:received,damaged,expired,count,other',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // For subtraction, validate sufficient stock
            if ($request->type === 'subtraction') {
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->quantity < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => 0,
                            'message' => "Insufficient stock for product: {$product->name}. Available: {$product->quantity}"
                        ], 422);
                    }
                }
            }

            $adjustment = StockAdjustment::create([
                'reference' => 'ADJ-' . str_pad(StockAdjustment::count() + 1, 3, '0', STR_PAD_LEFT),
                'store_id'  => $request->store_id,
                'date'      => $request->date,
                'type'      => $request->type,
                'reason'    => $request->reason,
                'note'      => $request->note,
            ]);

            foreach ($request->items as $item) {
                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id'          => $item['product_id'],
                    'quantity'            => $item['quantity'],
                ]);

                if ($request->type === 'addition') {
                    Product::where('id', $item['product_id'])->increment('quantity', $item['quantity']);
                } else {
                    Product::where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Add', 'Adjustments', "Created stock adjustment {$adjustment->reference}");
            return response()->json(['success' => 1, 'message' => 'Stock adjustment created successfully', 'data' => $adjustment->load(['store', 'items.product'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to create stock adjustment', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAdjustment(Request $request, $id)
    {
        $adjustment = StockAdjustment::with('items')->find($id);
        if (!$adjustment) {
            return response()->json(['success' => 0, 'message' => 'Stock adjustment not found'], 404);
        }

        $request->validate([
            'store_id'           => 'required|exists:stores,id',
            'date'               => 'required|date',
            'type'               => 'required|in:addition,subtraction',
            'reason'             => 'required|in:received,damaged,expired,count,other',
            'note'               => 'nullable|string',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            // Reverse old stock effect
            foreach ($adjustment->items as $oldItem) {
                if ($adjustment->type === 'addition') {
                    Product::where('id', $oldItem->product_id)->decrement('quantity', $oldItem->quantity);
                } else {
                    Product::where('id', $oldItem->product_id)->increment('quantity', $oldItem->quantity);
                }
            }

            // Validate new stock for subtraction
            if ($request->type === 'subtraction') {
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product->quantity < $item['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'success' => 0,
                            'message' => "Insufficient stock for product: {$product->name}. Available: {$product->quantity}"
                        ], 422);
                    }
                }
            }

            $adjustment->items()->delete();

            $adjustment->update([
                'store_id' => $request->store_id,
                'date'     => $request->date,
                'type'     => $request->type,
                'reason'   => $request->reason,
                'note'     => $request->note,
            ]);

            foreach ($request->items as $item) {
                StockAdjustmentItem::create([
                    'stock_adjustment_id' => $adjustment->id,
                    'product_id'          => $item['product_id'],
                    'quantity'            => $item['quantity'],
                ]);

                if ($request->type === 'addition') {
                    Product::where('id', $item['product_id'])->increment('quantity', $item['quantity']);
                } else {
                    Product::where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Edit', 'Adjustments', "Updated stock adjustment {$adjustment->reference}");
            return response()->json(['success' => 1, 'message' => 'Stock adjustment updated successfully', 'data' => $adjustment->load(['store', 'items.product'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to update stock adjustment', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteAdjustment($id)
    {
        $adjustment = StockAdjustment::with('items')->find($id);
        if (!$adjustment) {
            return response()->json(['success' => 0, 'message' => 'Stock adjustment not found'], 404);
        }

        DB::beginTransaction();
        try {
            // Reverse stock effect
            foreach ($adjustment->items as $item) {
                if ($adjustment->type === 'addition') {
                    Product::where('id', $item->product_id)->decrement('quantity', $item->quantity);
                } else {
                    Product::where('id', $item->product_id)->increment('quantity', $item->quantity);
                }
            }

            $ref = $adjustment->reference;
            $adjustment->items()->delete();
            $adjustment->delete();
            DB::commit();
            ActivityLogService::log('Delete', 'Adjustments', "Deleted stock adjustment {$ref}");
            return response()->json(['success' => 1, 'message' => 'Stock adjustment deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to delete stock adjustment', 'error' => $e->getMessage()], 500);
        }
    }
}
