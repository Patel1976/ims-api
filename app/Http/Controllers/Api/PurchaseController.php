<?php

namespace App\Http\Controllers\Api;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Http\Controllers\Controller;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function getAllPurchases()
    {
        $purchases = Purchase::with(['supplier', 'store', 'items.product'])->get();
        return response()->json(['success' => 1, 'data' => $purchases], 200);
    }

    public function viewPurchase($id)
    {
        $purchase = Purchase::with(['supplier', 'store', 'items.product'])->find($id);
        if (!$purchase) {
            return response()->json(['success' => 0, 'message' => 'Purchase not found'], 404);
        }
        return response()->json(['success' => 1, 'message' => 'Purchase retrieved successfully', 'data' => $purchase], 200);
    }

    public function createPurchase(Request $request)
    {
        $request->validate([
            'supplier_id'            => 'required|exists:suppliers,id',
            'store_id'               => 'required|exists:stores,id',
            'date'                   => 'required|date',
            'tax'                    => 'nullable|numeric|min:0',
            'shipping'               => 'nullable|numeric|min:0',
            'paid'                   => 'nullable|numeric|min:0',
            'payment_status'         => 'required|in:paid,partial,unpaid',
            'status'                 => 'required|in:Ordered,Pending,Received',
            'note'                   => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_cost'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_cost']);
            $tax      = $request->tax ?? 0;
            $shipping = $request->shipping ?? 0;
            $grandTotal = $subtotal + $tax + $shipping;
            $paid     = $request->paid ?? 0;
            $due      = $grandTotal - $paid;

            $purchase = Purchase::create([
                'reference'      => 'PO-' . str_pad(Purchase::count() + 1, 3, '0', STR_PAD_LEFT),
                'supplier_id'    => $request->supplier_id,
                'store_id'       => $request->store_id,
                'date'           => $request->date,
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'shipping'       => $shipping,
                'grand_total'    => $grandTotal,
                'paid'           => $paid,
                'due'            => $due,
                'payment_status' => $request->payment_status,
                'status'         => $request->status,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['quantity'] * $item['unit_cost'],
                ]);

                // Increase stock if received
                if ($request->status === 'Received') {
                    Product::where('id', $item['product_id'])->increment('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Add', 'Purchases', "Created purchase order {$purchase->reference}");
            return response()->json(['success' => 1, 'message' => 'Purchase created successfully', 'data' => $purchase->load(['supplier', 'store', 'items.product'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to create purchase', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePurchase(Request $request, $id)
    {
        $purchase = Purchase::with('items')->find($id);
        if (!$purchase) {
            return response()->json(['success' => 0, 'message' => 'Purchase not found'], 404);
        }

        $request->validate([
            'supplier_id'            => 'required|exists:suppliers,id',
            'store_id'               => 'required|exists:stores,id',
            'date'                   => 'required|date',
            'tax'                    => 'nullable|numeric|min:0',
            'shipping'               => 'nullable|numeric|min:0',
            'paid'                   => 'nullable|numeric|min:0',
            'payment_status'         => 'required|in:paid,partial,unpaid',
            'status'                 => 'required|in:Ordered,Pending,Received',
            'note'                   => 'nullable|string',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.unit_cost'      => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // Reverse old stock if was Received
            if ($purchase->status === 'Received') {
                foreach ($purchase->items as $oldItem) {
                    Product::where('id', $oldItem->product_id)->decrement('quantity', $oldItem->quantity);
                }
            }

            $purchase->items()->delete();

            $subtotal   = collect($request->items)->sum(fn($i) => $i['quantity'] * $i['unit_cost']);
            $tax        = $request->tax ?? 0;
            $shipping   = $request->shipping ?? 0;
            $grandTotal = $subtotal + $tax + $shipping;
            $paid       = $request->paid ?? 0;
            $due        = $grandTotal - $paid;

            $purchase->update([
                'supplier_id'    => $request->supplier_id,
                'store_id'       => $request->store_id,
                'date'           => $request->date,
                'subtotal'       => $subtotal,
                'tax'            => $tax,
                'shipping'       => $shipping,
                'grand_total'    => $grandTotal,
                'paid'           => $paid,
                'due'            => $due,
                'payment_status' => $request->payment_status,
                'status'         => $request->status,
                'note'           => $request->note,
            ]);

            foreach ($request->items as $item) {
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_cost'   => $item['unit_cost'],
                    'total'       => $item['quantity'] * $item['unit_cost'],
                ]);

                if ($request->status === 'Received') {
                    Product::where('id', $item['product_id'])->increment('quantity', $item['quantity']);
                }
            }

            DB::commit();
            ActivityLogService::log('Edit', 'Purchases', "Updated purchase order {$purchase->reference}");
            return response()->json(['success' => 1, 'message' => 'Purchase updated successfully', 'data' => $purchase->load(['supplier', 'store', 'items.product'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => 0, 'message' => 'Failed to update purchase', 'error' => $e->getMessage()], 500);
        }
    }

    public function deletePurchase($id)
    {
        $purchase = Purchase::with('items')->find($id);
        if (!$purchase) {
            return response()->json(['success' => 0, 'message' => 'Purchase not found'], 404);
        }

        // Reverse stock if was Received
        if ($purchase->status === 'Received') {
            foreach ($purchase->items as $item) {
                Product::where('id', $item->product_id)->decrement('quantity', $item->quantity);
            }
        }

        $ref = $purchase->reference;
        $purchase->items()->delete();
        $purchase->delete();
        ActivityLogService::log('Delete', 'Purchases', "Deleted purchase order {$ref}");
        return response()->json(['success' => 1, 'message' => 'Purchase deleted successfully'], 200);
    }
}
