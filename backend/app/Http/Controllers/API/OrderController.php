<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Store a new order
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

            // Create order
            $order = Order::create([
                'customer_first_name' => $validated['customer']['firstName'],
                'customer_last_name' => $validated['customer']['lastName'],
                'customer_email' => $validated['customer']['email'],
                'customer_phone' => $validated['customer']['phone'],
                'customer_address' => $validated['customer']['address'],
                'customer_district' => $validated['customer']['district'],
                'customer_state' => $validated['customer']['state'] ?? null,
                'customer_zip_code' => $validated['customer']['zipCode'] ?? null,
                'subtotal' => $validated['subtotal'],
                'shipping_method' => $validated['shipping_method'],
                'shipping_cost' => $validated['shipping_cost'],
                'total' => $validated['total'],
                'payment_method' => $validated['payment_method'],
                'status' => 'pending'
            ]);

            // Create order items
            foreach ($validated['items'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'product_image' => $item['image'] ?? null
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully',
                'data' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total
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
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders
     */
    public function index()
    {
        $orders = Order::with('items')->orderBy('created_at', 'desc')->get();
        
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
        $order = Order::with('items')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $order
        ]);
    }
}
