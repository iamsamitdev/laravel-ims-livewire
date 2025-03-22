<?php

namespace App\Http\Controllers\Dashboards;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Quotation;

class DashboardController extends Controller
{
    public function index()
    {
        $orders = Order::count();
        $completedOrders = Order::where('order_status', OrderStatus::COMPLETE)
            ->count();

        $products = Product::count();

        $purchases = Purchase::count();
        $todayPurchases = Purchase::query()
            ->where('date', today())
            ->get()
            ->count();

        $categories = Category::count();

        $quotations = Quotation::count();
        $todayQuotations = Quotation::query()
            ->where('date', today()->format('Y-m-d'))
            ->get()
            ->count();

        // เพิ่มข้อมูลสำหรับกราฟ
        $last30Days = collect(range(29, 0))->map(function ($days) {
            return now()->subDays($days)->format('Y-m-d');
        });

        $dailyOrders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $dailyPurchases = Purchase::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->whereDate('date', '>=', now()->subDays(30))
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        $orderData = $last30Days->map(function ($date) use ($dailyOrders) {
            return $dailyOrders[$date] ?? 0;
        })->toArray();

        $purchaseData = $last30Days->map(function ($date) use ($dailyPurchases) {
            return $dailyPurchases[$date] ?? 0;
        })->toArray();

        return view('dashboard', [
            'products' => $products,
            'orders' => $orders,
            'completedOrders' => $completedOrders,
            'purchases' => $purchases,
            'todayPurchases' => $todayPurchases,
            'categories' => $categories,
            'quotations' => $quotations,
            'todayQuotations' => $todayQuotations,
            'chartDates' => $last30Days->toArray(),
            'orderData' => $orderData,
            'purchaseData' => $purchaseData,
        ]);
    }
}
