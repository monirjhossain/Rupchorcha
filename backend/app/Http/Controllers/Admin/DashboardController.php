<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Webkul\Sales\Models\Order;
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
            // Use Bagisto repositories for accurate statistics
            $orderRepository = app(\Webkul\Sales\Repositories\OrderRepository::class);
            $customerRepository = app(\Webkul\Customer\Repositories\CustomerRepository::class);
            $productRepository = app(\Webkul\Product\Repositories\ProductRepository::class);

            $statistics = [
                'total_orders' => $orderRepository->count(),
                'total_customers' => $customerRepository->count(),
                'total_products' => $productRepository->count(),
                'pending_orders' => $orderRepository->findWhere(['status' => 'pending'])->count(),
            ];

            // Get recent orders (use Eloquent directly)
            $recentOrders = \Webkul\Sales\Models\Order::with('items')->orderBy('created_at', 'desc')->limit(10)->get();

            // Get top selling products and their images
            $topProductsRaw = DB::table('order_items')
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->select('products.id as product_id', 'products.name as product_name', DB::raw('SUM(order_items.quantity) as total_sold'), DB::raw('SUM(order_items.price * order_items.quantity) as total_revenue'))
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('total_sold')
                ->limit(5)
                ->get();

            // Attach product image if available
            $topProducts = collect();
            foreach ($topProductsRaw as $product) {
                $image = DB::table('product_images')->where('product_id', $product->product_id)->orderBy('position')->value('path');
                if ($image) {
                    // Ensure image path is accessible via public/storage
                    $imageUrl = (strpos($image, 'storage/') === 0) ? asset($image) : asset('storage/' . ltrim($image, '/'));
                } else {
                    $imageUrl = asset('images/placeholder.png');
                }
                $product->product_image = $imageUrl;
                $topProducts->push($product);
            }

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
            ));
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
