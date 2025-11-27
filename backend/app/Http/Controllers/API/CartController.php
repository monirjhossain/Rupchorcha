<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Webkul\Checkout\Models\Cart;
use Webkul\Checkout\Models\CartItem;
use Webkul\Product\Models\Product;

class CartController extends Controller
{
    /**
     * Get cart
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // Get or create cart
            $cart = $this->getOrCreateCart();

            // Load cart items with product details
            $cart->load(['items.product.images']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $cart->id,
                    'items_count' => $cart->items_count ?? $cart->items->count(),
                    'items' => $cart->items->map(function($item) {
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                            'product' => [
                                'id' => $item->product->id,
                                'name' => $item->product->name,
                                'price' => $item->product->price,
                                'image' => $item->product->images->first()->path ?? null,
                            ]
                        ];
                    })
                ],
                'message' => 'Cart retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cart index error: ' . $e->getMessage());
            return response()->json([
                'success' => true,
                'data' => ['items' => [], 'items_count' => 0],
                'message' => 'Empty cart'
            ]);
        }
    }

    /**
     * Add item to cart
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'nullable|integer|min:1'
            ]);

            $productId = $request->product_id;
            $quantity = $request->quantity ?? 1;

            // Get product
            $product = Product::find($productId);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product not found'
                ], 404);
            }

            // Get or create cart
            $cart = $this->getOrCreateCart();

            // Check if product already in cart
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                // Update quantity
                $cartItem->quantity += $quantity;
                $cartItem->total = $cartItem->quantity * $cartItem->price;
                $cartItem->save();
            } else {
                // Create new cart item
                $cartItem = new CartItem();
                $cartItem->cart_id = $cart->id;
                $cartItem->product_id = $productId;
                $cartItem->sku = $product->sku;
                $cartItem->type = $product->type;
                $cartItem->name = $product->name;
                $cartItem->quantity = $quantity;
                $cartItem->price = $product->price;
                $cartItem->base_price = $product->price;
                $cartItem->total = $quantity * $product->price;
                $cartItem->base_total = $quantity * $product->price;
                $cartItem->weight = $product->weight ?? 0;
                $cartItem->save();
            }

            // Update cart items count
            $cart->items_count = CartItem::where('cart_id', $cart->id)->sum('quantity');
            $cart->grand_total = CartItem::where('cart_id', $cart->id)->sum('total');
            $cart->base_grand_total = $cart->grand_total;
            $cart->sub_total = $cart->grand_total;
            $cart->base_sub_total = $cart->grand_total;
            $cart->save();

            return response()->json([
                'success' => true,
                'data' => [
                    'cart' => $cart,
                    'item' => $cartItem
                ],
                'message' => 'Product added to cart successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Cart add error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to add product to cart: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get or create cart
     *
     * @return Cart
     */
    private function getOrCreateCart()
    {
        // Try to get cart from session
        $cartId = session()->get('cart_id');
        
        if ($cartId) {
            $cart = Cart::find($cartId);
            if ($cart) {
                return $cart;
            }
        }

        // Create new cart
        $cart = new Cart();
        $cart->customer_id = auth()->guard('customer')->id() ?? null;
        $cart->is_guest = auth()->guard('customer')->check() ? 0 : 1;
        $cart->channel_id = core()->getCurrentChannel()->id;
        $cart->global_currency_code = core()->getCurrentCurrencyCode();
        $cart->base_currency_code = core()->getBaseCurrencyCode();
        $cart->channel_currency_code = core()->getChannelBaseCurrencyCode();
        $cart->cart_currency_code = core()->getCurrentCurrencyCode();
        $cart->items_count = 0;
        $cart->items_qty = 0;
        $cart->save();

        // Store cart ID in session
        session()->put('cart_id', $cart->id);

        return $cart;
    }

    /**
     * Update cart item
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $request->validate([
                'item_id' => 'required|integer',
                'quantity' => 'required|integer|min:1'
            ]);

            $cartItem = CartItem::find($request->item_id);
            
            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }

            $cartItem->quantity = $request->quantity;
            $cartItem->total = $cartItem->quantity * $cartItem->price;
            $cartItem->save();

            // Update cart totals
            $cart = Cart::find($cartItem->cart_id);
            $cart->items_count = CartItem::where('cart_id', $cart->id)->sum('quantity');
            $cart->grand_total = CartItem::where('cart_id', $cart->id)->sum('total');
            $cart->save();

            return response()->json([
                'success' => true,
                'message' => 'Cart updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove item from cart
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $cartItem = CartItem::find($id);
            
            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart item not found'
                ], 404);
            }

            $cartId = $cartItem->cart_id;
            $cartItem->delete();

            // Update cart totals
            $cart = Cart::find($cartId);
            if ($cart) {
                $cart->items_count = CartItem::where('cart_id', $cart->id)->sum('quantity');
                $cart->grand_total = CartItem::where('cart_id', $cart->id)->sum('total');
                $cart->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Item removed from cart successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
