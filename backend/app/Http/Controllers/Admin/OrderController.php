<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Sales\Models\Order;
use PDF;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display order listing
     */
    public function index(Request $request)
    {
        $query = Order::with('items');

        // Search by order number, customer name, email, or phone
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_first_name', 'like', "%{$search}%")
                  ->orWhere('customer_last_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->has('payment_method') && $request->payment_method) {
            $query->where('payment_method', $request->payment_method);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(20);

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
     * Download invoice PDF
     */
    public function downloadInvoice($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        $pdf = PDF::loadView('admin.orders.invoice', compact('order'));
        $pdf->setPaper('A4', 'portrait');
        return $pdf->download('invoice-' . $order->order_number . '.pdf');
    }

    /**
     * Download packing slip PDF
     */
    public function downloadPackingSlip($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        $pdf = PDF::loadView('admin.orders.packing-slip', compact('order'));
        $pdf->setPaper('A4', 'portrait');
        
        return $pdf->download('packing-slip-' . $order->order_number . '.pdf');
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
