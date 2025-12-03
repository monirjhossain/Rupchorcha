<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Sales\Models\OrderAddress;

class OrderController extends Controller
{
    /**
     * Store a new order (Bagisto compatible)
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'customer.firstName' => 'required|string',
                'customer.lastName' => 'required|string',
                'customer.email' => 'required|email',
                'customer.phone' => 'required|string',
                'customer.address' => 'required|string',
                'customer.district' => 'required|string',
                'customer.state' => 'nullable|string',
                'customer.zipCode' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required',
                'items.*.name' => 'required|string',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric',
                'items.*.image' => 'nullable|string',
                'subtotal' => 'required|numeric',
                'shipping_method' => 'required|string',
                'shipping_cost' => 'required|numeric',
                'total' => 'required|numeric',
                'payment_method' => 'required|string',
            ]);

            DB::beginTransaction();

            // Generate order increment ID
            $lastOrder = Order::orderBy('id', 'desc')->first();
            $incrementId = $lastOrder ? $lastOrder->id + 1 : 1;

            // Create order following Bagisto structure
            $order = Order::create([
                'increment_id' => str_pad($incrementId, 9, '0', STR_PAD_LEFT),
                'status' => 'pending',
                'channel_name' => 'Default',
                'is_guest' => 1,
                'customer_email' => $validated['customer']['email'],
                'customer_first_name' => $validated['customer']['firstName'],
                'customer_last_name' => $validated['customer']['lastName'],
                'customer_phone' => $validated['customer']['phone'],
                'shipping_method' => $validated['shipping_method'],
                'shipping_title' => $this->getShippingTitle($validated['shipping_method']),
                'base_currency_code' => 'BDT',
                'channel_currency_code' => 'BDT',
                'order_currency_code' => 'BDT',
                'grand_total' => $validated['total'],
                'base_grand_total' => $validated['total'],
                'sub_total' => $validated['subtotal'],
                'base_sub_total' => $validated['subtotal'],
                'shipping_amount' => $validated['shipping_cost'],
                'base_shipping_amount' => $validated['shipping_cost'],
                'total_item_count' => count($validated['items']),
                'total_qty_ordered' => array_sum(array_column($validated['items'], 'quantity')),
            ]);

            // Create shipping address
            OrderAddress::create([
                'order_id' => $order->id,
                'first_name' => $validated['customer']['firstName'],
                'last_name' => $validated['customer']['lastName'],
                'email' => $validated['customer']['email'],
                'phone' => $validated['customer']['phone'],
                'address1' => $validated['customer']['address'],
                'country' => 'Bangladesh',
                'state' => $validated['customer']['state'] ?? $validated['customer']['district'],
                'city' => $validated['customer']['district'],
                'postcode' => $validated['customer']['zipCode'] ?? '1400',
                'address_type' => 'shipping',
            ]);

            // Create billing address (same as shipping for now)
            OrderAddress::create([
                'order_id' => $order->id,
                'first_name' => $validated['customer']['firstName'],
                'last_name' => $validated['customer']['lastName'],
                'email' => $validated['customer']['email'],
                'phone' => $validated['customer']['phone'],
                'address1' => $validated['customer']['address'],
                'country' => 'Bangladesh',
                'state' => $validated['customer']['state'] ?? $validated['customer']['district'],
                'city' => $validated['customer']['district'],
                'postcode' => $validated['customer']['zipCode'] ?? '1400',
                'address_type' => 'billing',
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'sku' => 'SKU-' . $item['product_id'],
                    'type' => 'simple',
                    'qty_ordered' => $item['quantity'],
                    'price' => $item['price'],
                    'base_price' => $item['price'],
                    'total' => $item['price'] * $item['quantity'],
                    'base_total' => $item['price'] * $item['quantity'],
                    'additional' => json_encode([
                        'image' => $item['image'] ?? null,
                    ]),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->increment_id,
                    'total' => $order->grand_total
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get shipping title from method code
     */
    private function getShippingTitle($method)
    {
        $titles = [
            'inside_dhaka' => 'Inside Dhaka',
            'outside_dhaka' => 'Outside Dhaka',
        ];
        
        return $titles[$method] ?? 'Standard Shipping';
    }

    /**
     * Get all orders
     */
    public function index()
    {
        $orders = Order::with(['items', 'addresses'])
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $orders
        ]);
    }

    /**
     * Get single order
     */
    public function show($id)
    {
        $order = Order::with(['items', 'addresses'])->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
