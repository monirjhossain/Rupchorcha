<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display order listing
     */
    public function index()
    {
        $orders = Order::with('items')->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->status = $request->status;
            $order->save();

            return redirect()->back()->with('success', 'Order status updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create invoice
     */
    public function createInvoice(Request $request, $id)
    {
        return redirect()->back()->with('info', 'Invoice feature coming soon!');
    }

    /**
     * Create shipment
     */
    public function createShipment(Request $request, $id)
    {
        return redirect()->back()->with('info', 'Shipment feature coming soon!');
    }

    /**
     * Cancel order
     */
    public function cancel($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->status = 'cancelled';
            $order->save();

            return redirect()->back()->with('success', 'Order cancelled successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
