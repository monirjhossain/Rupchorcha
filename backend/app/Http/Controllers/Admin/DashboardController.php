<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Product\Repositories\ProductRepository;

class DashboardController extends Controller
{
    protected $orderRepository;
    protected $customerRepository;
    protected $productRepository;

    public function __construct(
        OrderRepository $orderRepository,
        CustomerRepository $customerRepository,
        ProductRepository $productRepository
    ) {
        $this->middleware('auth:admin');
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Display admin dashboard
     */
    public function index()
    {
        // Get statistics
        $statistics = [
            'total_orders' => $this->orderRepository->count(),
            'total_customers' => $this->customerRepository->count(),
            'total_products' => $this->productRepository->count(),
            'pending_orders' => $this->orderRepository->findWhere(['status' => 'pending'])->count(),
        ];

        // Get recent orders
        $recentOrders = $this->orderRepository->orderBy('created_at', 'desc')->limit(10)->get();

        // Get top selling products
        $topProducts = $this->productRepository->getTopSellingProducts(5);

        // Get revenue data (last 7 days)
        $revenueData = $this->getRevenueData();

        return view('admin.dashboard.index', compact(
            'statistics',
            'recentOrders',
            'topProducts',
            'revenueData'
        ));
    }

    /**
     * Get revenue data for charts
     */
    protected function getRevenueData()
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $orders = $this->orderRepository->whereBetween('created_at', [
                $date->startOfDay(),
                $date->endOfDay()
            ])->get();
            
            $data[] = [
                'date' => $date->format('M d'),
                'revenue' => $orders->sum('grand_total'),
                'orders' => $orders->count(),
            ];
        }
        
        return $data;
    }
}
