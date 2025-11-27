<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Customer\Repositories\CustomerRepository;
use Webkul\Customer\Repositories\CustomerGroupRepository;

class CustomerController extends Controller
{
    protected $customerRepository;
    protected $customerGroupRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CustomerGroupRepository $customerGroupRepository
    ) {
        $this->middleware('auth:admin');
        $this->customerRepository = $customerRepository;
        $this->customerGroupRepository = $customerGroupRepository;
    }

    /**
     * Display customer listing
     */
    public function index()
    {
        $customers = $this->customerRepository->paginate(20);

        return view('admin.customers.index', compact('customers'));
    }

    /**
     * Show create customer form
     */
    public function create()
    {
        $customerGroups = $this->customerGroupRepository->all();

        return view('admin.customers.create', compact('customerGroups'));
    }

    /**
     * Store new customer
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|min:6',
            'customer_group_id' => 'required',
        ]);

        try {
            $this->customerRepository->create($request->all());

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit customer form
     */
    public function edit($id)
    {
        $customer = $this->customerRepository->findOrFail($id);
        $customerGroups = $this->customerGroupRepository->all();

        return view('admin.customers.edit', compact('customer', 'customerGroups'));
    }

    /**
     * Update customer
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email|unique:customers,email,' . $id,
        ]);

        try {
            $this->customerRepository->update($request->all(), $id);

            return redirect()->back()->with('success', 'Customer updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete customer
     */
    public function destroy($id)
    {
        try {
            $this->customerRepository->delete($id);

            return redirect()->route('admin.customers.index')
                ->with('success', 'Customer deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mass delete customers
     */
    public function massDestroy(Request $request)
    {
        try {
            $customerIds = explode(',', $request->input('indexes'));
            
            foreach ($customerIds as $customerId) {
                $this->customerRepository->delete($customerId);
            }

            return redirect()->back()->with('success', 'Customers deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
