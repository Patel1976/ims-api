<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getDashboardData()
    {
        $stockAlertThreshold = (int) Setting::where('key', 'stock_alert_threshold')->value('value') ?? 10;

        // ── Main Stats ────────────────────────────────────────────────────────
        $totalProducts  = Product::count();
        $totalPurchases = Purchase::sum('grand_total');
        $totalSales     = Sale::where('status', 'Completed')->sum('grand_total');
        $totalCustomers = Customer::count();

        // Trends: compare this month vs last month
        $thisMonth = now()->month;
        $thisYear  = now()->year;
        $lastMonth = now()->subMonth()->month;
        $lastYear  = now()->subMonth()->year;

        $salesThisMonth = Sale::where('status', 'Completed')
            ->whereMonth('date', $thisMonth)->whereYear('date', $thisYear)
            ->sum('grand_total');

        $salesLastMonth = Sale::where('status', 'Completed')
            ->whereMonth('date', $lastMonth)->whereYear('date', $lastYear)
            ->sum('grand_total');

        $purchasesThisMonth = Purchase::whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->sum('grand_total');
        $purchasesLastMonth = Purchase::whereMonth('date', $lastMonth)->whereYear('date', $lastYear)->sum('grand_total');

        $customersThisMonth = Customer::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();
        $customersLastMonth = Customer::whereMonth('created_at', $lastMonth)->whereYear('created_at', $lastYear)->count();

        $productsThisMonth = Product::whereMonth('created_at', $thisMonth)->whereYear('created_at', $thisYear)->count();
        $productsLastMonth = Product::whereMonth('created_at', $lastMonth)->whereYear('created_at', $lastYear)->count();

        // ── Advanced Stats (this month) ───────────────────────────────────────
        $monthlySales = Sale::with('items.product')
            ->where('status', 'Completed')
            ->whereMonth('date', $thisMonth)
            ->whereYear('date', $thisYear)
            ->get();

        $grossRevenue = $monthlySales->sum('grand_total');

        $costOfGoods = $monthlySales->sum(function ($sale) {
            return $sale->items->sum(fn($item) => $item->product->purchase_price * $item->quantity);
        });

        $taxCollected = $monthlySales->sum('tax');

        $monthlyExpenses = Expense::whereMonth('date', $thisMonth)->whereYear('date', $thisYear)->sum('amount');

        $netProfit = $grossRevenue - $costOfGoods - $monthlyExpenses;
        $grossLoss = $netProfit < 0 ? abs($netProfit) : 0;

        // ── Recent Sales (last 5) ─────────────────────────────────────────────
        $recentSales = Sale::with('customer')
            ->orderByDesc('date')
            ->limit(5)
            ->get()
            ->map(fn($sale) => [
                'id'       => $sale->reference,
                'customer' => $sale->customer?->name ?? 'N/A',
                'date'     => $sale->date,
                'amount'   => $sale->grand_total,
                'status'   => $sale->status,
            ]);

        // ── Low Stock Products ────────────────────────────────────────────────
        $lowStockProducts = Product::where('quantity', '<=', $stockAlertThreshold)
            ->orderBy('quantity')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'category'  => $p->category,
                'stock'     => $p->quantity,
                'min_stock' => $stockAlertThreshold,
                'image'     => $p->image,
            ]);

        // ── Top Selling Products (by qty sold in completed sales) ─────────────
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.status', 'Completed')
            ->select(
                'products.id',
                'products.name',
                'products.category',
                'products.image',
                DB::raw('SUM(sale_items.quantity) as total_sold'),
                DB::raw('SUM(sale_items.total) as total_revenue')
            )
            ->groupBy('products.id', 'products.name', 'products.category', 'products.image')
            ->orderByDesc('total_sold')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => 1,
            'data'    => [
                'stats' => [
                    'total_products'  => $totalProducts,
                    'total_purchases' => $totalPurchases,
                    'total_sales'     => $totalSales,
                    'total_customers' => $totalCustomers,
                    'trends' => [
                        'products'  => $this->calcTrend($productsThisMonth, $productsLastMonth),
                        'purchases' => $this->calcTrend($purchasesThisMonth, $purchasesLastMonth),
                        'sales'     => $this->calcTrend($salesThisMonth, $salesLastMonth),
                        'customers' => $this->calcTrend($customersThisMonth, $customersLastMonth),
                    ],
                ],
                'advanced_stats' => [
                    'net_profit'    => max($netProfit, 0),
                    'gross_loss'    => $grossLoss,
                    'tax_collected' => $taxCollected,
                ],
                'recent_sales'      => $recentSales,
                'low_stock_products' => $lowStockProducts,
                'top_products'      => $topProducts,
            ],
        ], 200);
    }

    private function calcTrend($current, $previous): array
    {
        if ($previous == 0) {
            $percent = $current > 0 ? 100 : 0;
        } else {
            $percent = round((($current - $previous) / $previous) * 100, 1);
        }

        return [
            'percentage' => abs($percent),
            'trend_up'   => $percent >= 0,
            'label'      => ($percent >= 0 ? '+' : '-') . abs($percent) . '%',
        ];
    }
}
