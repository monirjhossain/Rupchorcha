<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Checkout\Repositories\CartRepository;
use Webkul\Checkout\Repositories\CartItemRepository;

class CartController extends Controller
{
    protected $cartRepository;
    protected $cartItemRepository;

    public function __construct(
        CartRepository $cartRepository,
        CartItemRepository $cartItemRepository
    ) {
        $this->cartRepository = $cartRepository;
        $this->cartItemRepository = $cartItemRepository;
    }

    /**
     * Display cart
     */
    public function index()
    {
        $cart = $this->cartRepository->getCart();

        return view('frontend.cart.index', compact('cart'));
    }

    /**
     * Add product to cart
     */
    public function store(Request $request)
    {
        try {
            $cart = $this->cartRepository->addProduct($request->product_id, $request->all());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Product added to cart successfully!',
                    'cart' => $cart,
                ]);
            }

            return redirect()->back()->with('success', 'Product added to cart successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update cart
     */
    public function update(Request $request)
    {
        try {
            $this->cartRepository->updateItems($request->all());

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Cart updated successfully!',
                ]);
            }

            return redirect()->back()->with('success', 'Cart updated successfully!');
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove item from cart
     */
    public function destroy($id)
    {
        try {
            $this->cartItemRepository->delete($id);

            return redirect()->back()->with('success', 'Item removed from cart successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Empty cart
     */
    public function empty()
    {
        try {
            $this->cartRepository->deActivateCart();

            return redirect()->route('cart.index')->with('success', 'Cart emptied successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
