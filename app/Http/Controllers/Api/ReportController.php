<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ─── Sales Report ────────────────────────────────────────────────────────────
    public function salesReport(Request $request)
    {
        $request->validate([
            'from_date'   => 'nullable|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $query = Sale::with(['customer', 'store', 'items.product'])
            ->when($request->from_date, fn($q) => $q->whereDate('date', '>=', $request->from_date))
            ->when($request->to_date,   fn($q) => $q->whereDate('date', '<=', $request->to_date))
            ->when($request->customer_id, fn($q) => $q->where('customer_id', $request->customer_id))
            ->orderByDesc('date');

        $sales = $query->get();

        // Profit = grand_total - sum of (purchase_price * qty) per item
        $totalProfit = $sales->sum(function ($sale) {
            return $sale->items->sum(function ($item) {
                return ($item->unit_price - $item->product->purchase_price) * $item->quantity;
            });
        });

        $summary = [
            'total_sales'    => $sales->sum('grand_total'),
            'total_profit'   => round($totalProfit, 2),
            'total_products' => $sales->sum(fn($s) => $s->items->sum('quantity')),
            'total_orders'   => $sales->count(),
        ];

        return response()->json(['success' => 1, 'summary' => $summary, 'data' => $sales], 200);
    }

    // ─── Purchase Report ─────────────────────────────────────────────────────────
    public function purchaseReport(Request $request)
    {
        $request->validate([
            'from_date'   => 'nullable|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'search'      => 'nullable|string',
        ]);

        $purchases = Purchase::with(['supplier', 'store', 'items.product'])
            ->when($request->from_date,   fn($q) => $q->whereDate('date', '>=', $request->from_date))
            ->when($request->to_date,     fn($q) => $q->whereDate('date', '<=', $request->to_date))
            ->when($request->supplier_id, fn($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('reference', 'like', "%{$request->search}%")
                  ->orWhereHas('supplier', fn($q) => $q->where('name', 'like', "%{$request->search}%"));
            }))
            ->orderByDesc('date')
            ->get();

        $summary = [
            'total_purchases' => $purchases->sum('grand_total'),
            'total_paid'      => $purchases->sum('paid'),
            'total_due'       => $purchases->sum('due'),
            'total_orders'    => $purchases->count(),
        ];

        return response()->json(['success' => 1, 'summary' => $summary, 'data' => $purchases], 200);
    }

    // ─── Inventory Report ────────────────────────────────────────────────────────
    public function inventoryReport(Request $request)
    {
        $request->validate([
            'category'     => 'nullable|string',
            'brand'        => 'nullable|string',
            'stock_status' => 'nullable|in:in-stock,low-stock,out-of-stock',
        ]);

        $alertThreshold = 10;

        $products = Product::query()
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->brand,    fn($q) => $q->where('brand', $request->brand))
            ->when($request->stock_status, function ($q) use ($request, $alertThreshold) {
                match ($request->stock_status) {
                    'in-stock'      => $q->where('quantity', '>', $alertThreshold),
                    'low-stock'     => $q->whereBetween('quantity', [1, $alertThreshold]),
                    'out-of-stock'  => $q->where('quantity', 0),
                };
            })
            ->get();

        // Total sold = sum of all completed sale items per product
        $soldMap = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'Completed')
            ->select('sale_items.product_id', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->groupBy('sale_items.product_id')
            ->pluck('total_sold', 'product_id');

        $products = $products->map(function ($product) use ($soldMap) {
            $product->total_sold  = $soldMap[$product->id] ?? 0;
            $product->stock_value = $product->quantity * $product->purchase_price;
            return $product;
        });

        $summary = [
            'total_products'   => $products->count(),
            'total_stock_value' => $products->sum('stock_value'),
            'total_sold'       => $products->sum('total_sold'),
            'low_stock_items'  => $products->where('quantity', '<=', $alertThreshold)->where('quantity', '>', 0)->count(),
            'out_of_stock'     => $products->where('quantity', 0)->count(),
        ];

        return response()->json(['success' => 1, 'summary' => $summary, 'data' => $products], 200);
    }

    // ─── Customer Report ─────────────────────────────────────────────────────────
    public function customerReport(Request $request)
    {
        $request->validate([
            'from_date'   => 'nullable|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'customer_id' => 'nullable|exists:customers,id',
            'search'      => 'nullable|string',
        ]);

        $customers = Customer::query()
            ->when($request->customer_id, fn($q) => $q->where('id', $request->customer_id))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->get();

        $customers = $customers->map(function ($customer) use ($request) {
            $salesQuery = Sale::where('customer_id', $customer->id)
                ->when($request->from_date, fn($q) => $q->whereDate('date', '>=', $request->from_date))
                ->when($request->to_date,   fn($q) => $q->whereDate('date', '<=', $request->to_date));

            $customer->total_sales  = $salesQuery->sum('grand_total');
            $customer->total_paid   = $salesQuery->sum('paid');
            $customer->total_due    = $salesQuery->sum('due');
            $customer->total_orders = $salesQuery->count();
            return $customer;
        });

        $summary = [
            'total_customers' => $customers->count(),
            'total_sales'     => $customers->sum('total_sales'),
            'total_paid'      => $customers->sum('total_paid'),
            'total_due'       => $customers->sum('total_due'),
        ];

        return response()->json(['success' => 1, 'summary' => $summary, 'data' => $customers], 200);
    }

    // ─── Supplier Report ─────────────────────────────────────────────────────────
    public function supplierReport(Request $request)
    {
        $request->validate([
            'from_date'   => 'nullable|date',
            'to_date'     => 'nullable|date|after_or_equal:from_date',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'search'      => 'nullable|string',
        ]);

        $suppliers = Supplier::query()
            ->when($request->supplier_id, fn($q) => $q->where('id', $request->supplier_id))
            ->when($request->search, fn($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            }))
            ->get();

        $suppliers = $suppliers->map(function ($supplier) use ($request) {
            $purchasesQuery = Purchase::where('supplier_id', $supplier->id)
                ->when($request->from_date, fn($q) => $q->whereDate('date', '>=', $request->from_date))
                ->when($request->to_date,   fn($q) => $q->whereDate('date', '<=', $request->to_date));

            $supplier->total_purchases = $purchasesQuery->sum('grand_total');
            $supplier->total_paid      = $purchasesQuery->sum('paid');
            $supplier->total_due       = $purchasesQuery->sum('due');
            $supplier->total_orders    = $purchasesQuery->count();
            return $supplier;
        });

        $summary = [
            'total_suppliers' => $suppliers->count(),
            'total_purchases' => $suppliers->sum('total_purchases'),
            'total_paid'      => $suppliers->sum('total_paid'),
            'total_due'       => $suppliers->sum('total_due'),
        ];

        return response()->json(['success' => 1, 'summary' => $summary, 'data' => $suppliers], 200);
    }
}
