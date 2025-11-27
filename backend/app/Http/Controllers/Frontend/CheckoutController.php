<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Customer\Repositories\CustomerAddressRepository;

class CheckoutController extends Controller
{
    protected $cartRepository;
    protected $orderRepository;
    protected $customerAddressRepository;

    public function __construct(
        CartRepository $cartRepository,
        OrderRepository $orderRepository,
        CustomerAddressRepository $customerAddressRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->customerAddressRepository = $customerAddressRepository;
    }

    /**
     * Display checkout page
     */
    public function index()
    {
        $cart = $this->cartRepository->getCart();

        if (!$cart || $cart->items->count() == 0) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty!');
        }

        $addresses = [];
        if (auth()->guard('customer')->check()) {
            $addresses = $this->customerAddressRepository->findWhere([
                'customer_id' => auth()->guard('customer')->id()
            ]);
        }

        return view('frontend.checkout.index', compact('cart', 'addresses'));
    }

    /**
     * Save checkout addresses
     */
    public function saveAddress(Request $request)
    {
        $request->validate([
            'billing.first_name' => 'required|string',
            'billing.last_name' => 'required|string',
            'billing.email' => 'required|email',
            'billing.address1' => 'required|string',
            'billing.city' => 'required|string',
            'billing.state' => 'required|string',
            'billing.postcode' => 'required|string',
            'billing.country' => 'required|string',
            'billing.phone' => 'required|string',
        ]);

        try {
            $this->cartRepository->saveAddresses($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Address saved successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Save shipping method
     */
    public function saveShipping(Request $request)
    {
        $request->validate([
            'shipping_method' => 'required|string',
        ]);

        try {
            $this->cartRepository->saveShippingMethod($request->shipping_method);

            return response()->json([
                'success' => true,
                'message' => 'Shipping method saved successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Save payment method
     */
    public function savePayment(Request $request)
    {
        $request->validate([
            'payment' => 'required|array',
            'payment.method' => 'required|string',
        ]);

        try {
            $this->cartRepository->savePaymentMethod($request->payment);

            return response()->json([
                'success' => true,
                'message' => 'Payment method saved successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Place order
     */
    public function placeOrder()
    {
        try {
            $order = $this->orderRepository->create(
                $this->cartRepository->prepareDataForOrder()
            );

            $this->cartRepository->deActivateCart();

            return redirect()->route('checkout.success', $order->id);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Order success page
     */
    public function success($orderId)
    {
        $order = $this->orderRepository->find($orderId);

        return view('frontend.checkout.success', compact('order'));
    }
}
