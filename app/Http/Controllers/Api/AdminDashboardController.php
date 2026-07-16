<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // 1. Total Pendapatan (Status Paid/Shipped/Completed)
        $revenue = Order::whereIn('status', ['paid', 'shipped', 'completed'])->sum('total_price');

        // 2. Total Incoming Orders
        $totalOrders = Order::count();

        // 3. Total Member
        $totalUsers = User::where('role', 'member')->count();

        // 4. Produk Low Stock (Kurang dari 5)
        $lowStock = ProductVariant::with('product')
            ->where('stock', '<', 5)
            ->limit(5)
            ->get();

        // 5. Recent Orders (5 Terakhir)
        $recentOrders = Order::with('user')->latest()->limit(5)->get();

        return response()->json([
            'data' => [
                'revenue' => $revenue,
                'total_orders' => $totalOrders,
                'total_users' => $totalUsers,
                'total_products' => Product::count(),
                'low_stock' => $lowStock,
                'recent_orders' => $recentOrders
            ]
        ]);
    }
}
