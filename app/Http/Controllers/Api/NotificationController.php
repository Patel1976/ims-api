<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationRead;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\SaleReturn;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function getAuthUserId(): ?int
    {
        return auth()->id();
    }

    public function getNotifications()
    {
        $userId = $this->getAuthUserId();
        $stockThreshold = (int) Setting::where('key', 'stock_alert_threshold')->value('value') ?? 10;

        // Get all read keys for this user
        $readKeys = $userId
            ? NotificationRead::where('user_id', $userId)->pluck('notification_key')->flip()
            : collect();

        $notifications = [];

        // Low stock alerts
        Product::where('quantity', '<=', $stockThreshold)
            ->orderBy('quantity')->limit(10)->get()
            ->each(function ($product) use (&$notifications, $readKeys) {
                $key = 'stock-' . $product->id;
                $notifications[] = [
                    'id'      => $key,
                    'type'    => $product->quantity == 0 ? 'danger' : 'warning',
                    'title'   => $product->quantity == 0 ? 'Out of Stock' : 'Low Stock Alert',
                    'message' => $product->quantity == 0
                        ? "{$product->name} is out of stock"
                        : "{$product->name} stock is low ({$product->quantity} units left)",
                    'time'    => $this->timeAgo($product->updated_at),
                    'read'    => $readKeys->has($key),
                ];
            });

        // Recent completed sales (last 5)
        Sale::with('customer')->where('status', 'Completed')
            ->orderByDesc('created_at')->limit(5)->get()
            ->each(function ($sale) use (&$notifications, $readKeys) {
                $key = 'sale-' . $sale->id;
                $notifications[] = [
                    'id'      => $key,
                    'type'    => 'success',
                    'title'   => 'New Sale',
                    'message' => "Sale {$sale->reference} worth " . number_format($sale->grand_total, 2) .
                                 " from " . ($sale->customer?->name ?? 'N/A'),
                    'time'    => $this->timeAgo($sale->created_at),
                    'read'    => $readKeys->has($key),
                ];
            });

        // Recent received purchases (last 5)
        Purchase::with('supplier')->where('status', 'Received')
            ->orderByDesc('created_at')->limit(5)->get()
            ->each(function ($purchase) use (&$notifications, $readKeys) {
                $key = 'purchase-' . $purchase->id;
                $notifications[] = [
                    'id'      => $key,
                    'type'    => 'info',
                    'title'   => 'Purchase Received',
                    'message' => "Purchase order {$purchase->reference} received from " .
                                 ($purchase->supplier?->name ?? 'N/A'),
                    'time'    => $this->timeAgo($purchase->created_at),
                    'read'    => $readKeys->has($key),
                ];
            });

        // Pending purchase returns
        PurchaseReturn::where('status', 'Pending')
            ->orderByDesc('created_at')->limit(5)->get()
            ->each(function ($return) use (&$notifications, $readKeys) {
                $key = 'pr-' . $return->id;
                $notifications[] = [
                    'id'      => $key,
                    'type'    => 'warning',
                    'title'   => 'Pending Purchase Return',
                    'message' => "Purchase return {$return->reference} is pending approval",
                    'time'    => $this->timeAgo($return->created_at),
                    'read'    => $readKeys->has($key),
                ];
            });

        // Pending sale returns
        SaleReturn::where('status', 'Pending')
            ->orderByDesc('created_at')->limit(5)->get()
            ->each(function ($return) use (&$notifications, $readKeys) {
                $key = 'sr-' . $return->id;
                $notifications[] = [
                    'id'      => $key,
                    'type'    => 'warning',
                    'title'   => 'Pending Sale Return',
                    'message' => "Sale return {$return->reference} is pending approval",
                    'time'    => $this->timeAgo($return->created_at),
                    'read'    => $readKeys->has($key),
                ];
            });

        return response()->json(['success' => 1, 'data' => $notifications], 200);
    }

    public function markAsRead(Request $request)
    {
        $request->validate(['notification_key' => 'required|string']);
        $userId = $this->getAuthUserId();
        if (!$userId) return response()->json(['success' => 0, 'message' => 'Unauthenticated'], 401);

        NotificationRead::firstOrCreate([
            'user_id'          => $userId,
            'notification_key' => $request->notification_key,
        ]);

        return response()->json(['success' => 1, 'message' => 'Marked as read'], 200);
    }

    public function markAllAsRead(Request $request)
    {
        $request->validate(['keys' => 'required|array']);
        $userId = $this->getAuthUserId();
        if (!$userId) return response()->json(['success' => 0, 'message' => 'Unauthenticated'], 401);

        foreach ($request->keys as $key) {
            NotificationRead::firstOrCreate([
                'user_id'          => $userId,
                'notification_key' => $key,
            ]);
        }

        return response()->json(['success' => 1, 'message' => 'All marked as read'], 200);
    }

    private function timeAgo($datetime): string
    {
        if (!$datetime) return 'Just now';
        return Carbon::parse($datetime)->diffForHumans();
    }
}
