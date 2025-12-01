<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display admin dashboard
     */
    public function index()
    {
        try {
            // Get statistics
            $statistics = [
                'total_orders' => Order::count(),
                'total_customers' => DB::table('customers')->count(),
                'total_products' => DB::table('products')->count(),
                'pending_orders' => Order::where('status', 'pending')->count(),
            ];

            // Get recent orders
            $recentOrders = Order::with('items')->orderBy('created_at', 'desc')->limit(10)->get();

            // Get top selling products from order items
            $topProducts = DB::table('customer_order_items')
                ->select('product_name', 'product_image', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(price * quantity) as total_revenue'))
                ->groupBy('product_id', 'product_name', 'product_image')
                ->orderBy('total_sold', 'desc')
                ->limit(5)
                ->get();

            // Get revenue data (last 7 days)
            $revenueData = $this->getRevenueData();

            return view('admin.dashboard.index', compact(
                'statistics',
                'recentOrders',
                'topProducts',
                'revenueData'
            ));
        } catch (\Exception $e) {
            // If any error, show basic dashboard
            $statistics = [
                'total_orders' => 0,
                'total_customers' => 0,
                'total_products' => 0,
                'pending_orders' => 0,
            ];
            $recentOrders = collect();
            $topProducts = collect();
            $revenueData = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $revenueData[] = [
                    'date' => now()->subDays($i)->format('M d'),
                    'revenue' => 0,
                    'orders' => 0,
                ];
            }

            return view('admin.dashboard.index', compact(
                'statistics',
                'recentOrders',
                'topProducts',
                'revenueData'
            ))->withErrors(['error' => 'Database error: ' . $e->getMessage()]);
        }
    }

    /**
     * Get revenue data for charts
     */
    protected function getRevenueData()
    {
        $data = [];
        
        try {
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $orders = Order::whereBetween('created_at', [
                    $date->copy()->startOfDay(),
                    $date->copy()->endOfDay()
                ])->get();
                
                $data[] = [
                    'date' => $date->format('M d'),
                    'revenue' => $orders->sum('total'),
                    'orders' => $orders->count(),
                ];
            }
        } catch (\Exception $e) {
            // Return empty data if error
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i);
                $data[] = [
                    'date' => $date->format('M d'),
                    'revenue' => 0,
                    'orders' => 0,
                ];
            }
        }
        
        return $data;
    }
}
