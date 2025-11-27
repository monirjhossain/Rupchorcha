<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\ShipmentRepository;

class OrderController extends Controller
{
    protected $orderRepository;
    protected $invoiceRepository;
    protected $shipmentRepository;

    public function __construct(
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        ShipmentRepository $shipmentRepository
    ) {
        $this->middleware('auth:admin');
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * Display order listing
     */
    public function index()
    {
        $orders = $this->orderRepository->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Show order details
     */
    public function show($id)
    {
        $order = $this->orderRepository->findOrFail($id);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Update order status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $this->orderRepository->update([
                'status' => $request->status
            ], $id);

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
        try {
            $order = $this->orderRepository->findOrFail($id);
            
            $invoice = $this->invoiceRepository->create(
                array_merge($request->all(), ['order_id' => $order->id])
            );

            return redirect()->back()->with('success', 'Invoice created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Create shipment
     */
    public function createShipment(Request $request, $id)
    {
        try {
            $order = $this->orderRepository->findOrFail($id);
            
            $shipment = $this->shipmentRepository->create(
                array_merge($request->all(), ['order_id' => $order->id])
            );

            return redirect()->back()->with('success', 'Shipment created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel order
     */
    public function cancel($id)
    {
        try {
            $this->orderRepository->cancel($id);

            return redirect()->back()->with('success', 'Order cancelled successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
