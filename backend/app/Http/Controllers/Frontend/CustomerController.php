<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Repositories\CustomerAddressRepository;
use Webkul\Sales\Repositories\OrderRepository;

class CustomerController extends Controller
{
    protected $customerRepository;
    protected $customerAddressRepository;
    protected $orderRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerAddressRepository $customerAddressRepository,
        OrderRepository $orderRepository
    ) {
        $this->middleware('auth:customer');
        $this->customerRepository = $customerRepository;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Display customer dashboard
     */
    public function dashboard()
    {
        $customer = auth()->guard('customer')->user();
        $recentOrders = $this->orderRepository->findWhere([
            'customer_id' => $customer->id
        ])->take(5);

        return view('frontend.customer.dashboard', compact('customer', 'recentOrders'));
    }

    /**
     * Display customer profile
     */
    public function profile()
    {
        $customer = auth()->guard('customer')->user();

        return view('frontend.customer.profile', compact('customer'));
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . auth()->guard('customer')->id(),
            'phone' => 'nullable|string|max:20',
        ]);

        try {
            $this->customerRepository->update($request->all(), auth()->guard('customer')->id());

            return redirect()->back()->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display customer addresses
     */
    public function addresses()
    {
        $addresses = $this->customerAddressRepository->findWhere([
            'customer_id' => auth()->guard('customer')->id()
        ]);

        return view('frontend.customer.addresses', compact('addresses'));
    }

    /**
     * Store new address
     */
    public function storeAddress(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'address1' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'postcode' => 'required|string',
            'country' => 'required|string',
            'phone' => 'required|string',
        ]);

        try {
            $this->customerAddressRepository->create(array_merge($request->all(), [
                'customer_id' => auth()->guard('customer')->id()
            ]));

            return redirect()->back()->with('success', 'Address added successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display customer orders
     */
    public function orders()
    {
        $orders = $this->orderRepository->findWhere([
            'customer_id' => auth()->guard('customer')->id()
        ]);

        return view('frontend.customer.orders', compact('orders'));
    }

    /**
     * Display order details
     */
    public function orderDetails($orderId)
    {
        $order = $this->orderRepository->findOneWhere([
            'id' => $orderId,
            'customer_id' => auth()->guard('customer')->id()
        ]);

        if (!$order) {
            abort(404);
        }

        return view('frontend.customer.order-details', compact('order'));
    }
}
