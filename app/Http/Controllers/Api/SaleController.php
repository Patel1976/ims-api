<?php

namespace App\Http\Controllers\Api;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function getAllSales()
    {
        $sales = Sale::with(['customer', 'store', 'items.product'])->get();
        return response()->json(['success' => 1, 'data' => $sales], 200);
    }

    public function viewSale($id)
    {
        $sale = Sale::with(['customer', 'store', 'items.product'])->find($id);
        if (!$sale) {
            return response()->json(['success' => 0, 'message' => 'Sale not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Sale retrieved successfully', 'data' => $sale], 200);
    }

    public function createSale(Request $request)
    {
        $request->validate([
            'customer_id'            => 'required|exists:customers,id',
            'store_id'               => 'required|exists:stores,id',
            'date'                   => 'required|date',
            'tax'                    => 'nullable|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'paid'                   => 'nullable|numeric|min:0',
            'payment_status'         => 'required|in:paid,partial,unpaid',
            'status'                 => 'required|in:Pending,Completed',
            'note'                   => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Check stock availability
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

            $subtotal   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $tax        = $request->tax ?? 0;
            $discount   = $request->discount ?? 0;
            $grandTotal = $subtotal + $tax - $discount;
            $paid       = $request->paid ?? 0;
            $due        = $grandTotal - $paid;

            $sale = Sale::create([
                'reference'      => 'INV-' . str_pad(Sale::count() + 1, 3, '0', STR_PAD_LEFT),
                'customer_id'    => $request->customer_id,
                'store_id'       => $request->store_id,
                'date'           => $request->date,
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'discount'       => $discount,
                'grand_total'    => $grandTotal,
                'paid'           => $paid,
                'due'            => $due,
                'payment_status' => $request->payment_status,
                'status'         => $request->status,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total'      => $item['quantity'] * $item['unit_price'],
                ]);

                // Decrease stock when sale is Completed
                if ($request->status === 'Completed') {
                    Product::where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Add', 'Sales', "Created sale {$sale->reference}");
            return response()->json(['success' => 1, 'message' => 'Sale created successfully', 'data' => $sale->load(['customer', 'store', 'items.product'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to create sale', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateSale(Request $request, $id)
    {
        $sale = Sale::with('items')->find($id);
        if (!$sale) {
            return response()->json(['success' => 0, 'message' => 'Sale not found'], 404);
        }

        $request->validate([
            'customer_id'            => 'required|exists:customers,id',
            'store_id'               => 'required|exists:stores,id',
            'date'                   => 'required|date',
            'tax'                    => 'nullable|numeric|min:0',
            'discount'               => 'nullable|numeric|min:0',
            'paid'                   => 'nullable|numeric|min:0',
            'payment_status'         => 'required|in:paid,partial,unpaid',
            'status'                 => 'required|in:Pending,Completed',
            'note'                   => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_price'     => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Restore old stock if was Completed
            if ($sale->status === 'Completed') {
                foreach ($sale->items as $oldItem) {
                    Product::where('id', $oldItem->product_id)->increment('quantity', $oldItem->quantity);
                }
            }

            // Check new stock availability
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

            $sale->items()->delete();

            $subtotal   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_price']);
            $tax        = $request->tax ?? 0;
            $discount   = $request->discount ?? 0;
            $grandTotal = $subtotal + $tax - $discount;
            $paid       = $request->paid ?? 0;
            $due        = $grandTotal - $paid;

            $sale->update([
                'customer_id'    => $request->customer_id,
                'store_id'       => $request->store_id,
                'date'           => $request->date,
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'discount'       => $discount,
                'grand_total'    => $grandTotal,
                'paid'           => $paid,
                'due'            => $due,
                'payment_status' => $request->payment_status,
                'status'         => $request->status,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                SaleItem::create([
                    'sale_id'    => $sale->id,
                    'product_id' => $item['product_id'],
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total'      => $item['quantity'] * $item['unit_price'],
                ]);

                if ($request->status === 'Completed') {
                    Product::where('id', $item['product_id'])->decrement('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Edit', 'Sales', "Updated sale {$sale->reference}");
            return response()->json(['success' => 1, 'message' => 'Sale updated successfully', 'data' => $sale->load(['customer', 'store', 'items.product'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to update sale', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteSale($id)
    {
        $sale = Sale::with('items')->find($id);
        if (!$sale) {
            return response()->json(['success' => 0, 'message' => 'Sale not found'], 404);
        }

        // Restore stock if was Completed
        if ($sale->status === 'Completed') {
            foreach ($sale->items as $item) {
                Product::where('id', $item->product_id)->increment('quantity', $item->quantity);
            }
        }

        $ref = $sale->reference;
        $sale->items()->delete();
        $sale->delete();
        ActivityLogService::log('Delete', 'Sales', "Deleted sale {$ref}");
        return response()->json(['success' => 1, 'message' => 'Sale deleted successfully'], 200);
    }
}
