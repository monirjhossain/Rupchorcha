<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductBundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    /**
     * Display a listing of product bundles
     */
    public function index(Request $request)
    {
        $query = ProductBundle::with(['product', 'bundleProduct']);
        
        // Search by product name
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('product', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        
        $bundles = $query->ordered()
                        ->paginate(15);
        
        return view('admin.bundles.index', compact('bundles'));
    }

    /**
     * Show the form for creating a new bundle
     */
    public function create(Request $request)
    {
        $products = Product::where('status', 1)->get();
        $selectedProduct = null;
        
        if ($request->has('product_id')) {
            $selectedProduct = Product::find($request->product_id);
        }
        
        return view('admin.bundles.create', compact('products', 'selectedProduct'));
    }

    /**
     * Store a newly created bundle
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'bundle_product_id' => 'required|exists:products,id|different:product_id',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'position' => 'required|integer|min:0'
        ]);
        
        // Check if bundle already exists
        $exists = ProductBundle::where('product_id', $request->product_id)
                              ->where('bundle_product_id', $request->bundle_product_id)
                              ->exists();
        
        if ($exists) {
            return back()->withErrors(['bundle_product_id' => 'This product is already bundled.'])->withInput();
        }
        
        ProductBundle::create($request->all());
        
        return redirect()->route('admin.bundles.index')
                        ->with('success', 'Bundle created successfully');
    }

    /**
     * Show the form for editing bundle
     */
    public function edit($id)
    {
        $bundle = ProductBundle::with(['product', 'bundleProduct'])->findOrFail($id);
        $products = Product::where('status', 1)->get();
        
        return view('admin.bundles.edit', compact('bundle', 'products'));
    }

    /**
     * Update the bundle
     */
    public function update(Request $request, $id)
    {
        $bundle = ProductBundle::findOrFail($id);
        
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'bundle_product_id' => 'required|exists:products,id|different:product_id',
            'discount_percentage' => 'required|integer|min:0|max:100',
            'position' => 'required|integer|min:0'
        ]);
        
        // Check if bundle already exists (excluding current)
        $exists = ProductBundle::where('product_id', $request->product_id)
                              ->where('bundle_product_id', $request->bundle_product_id)
                              ->where('id', '!=', $id)
                              ->exists();
        
        if ($exists) {
            return back()->withErrors(['bundle_product_id' => 'This product is already bundled.'])->withInput();
        }
        
        $bundle->update($request->all());
        
        return redirect()->route('admin.bundles.index')
                        ->with('success', 'Bundle updated successfully');
    }

    /**
     * Remove the bundle
     */
    public function destroy($id)
    {
        $bundle = ProductBundle::findOrFail($id);
        $bundle->delete();
        
        return redirect()->route('admin.bundles.index')
                        ->with('success', 'Bundle deleted successfully');
    }

    /**
     * Mass destroy bundles
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:product_bundles,id'
        ]);
        
        ProductBundle::whereIn('id', $request->ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Bundles deleted successfully'
        ]);
    }
    
    /**
     * Search products via AJAX
     */
    public function searchProducts(Request $request)
    {
        $search = $request->get('q', '');
        $excludeId = $request->get('exclude_id');
        
        $products = Product::where('status', 1)
                          ->where('name', 'like', "%{$search}%")
                          ->when($excludeId, function($q) use ($excludeId) {
                              $q->where('id', '!=', $excludeId);
                          })
                          ->limit(10)
                          ->get(['id', 'name', 'price']);
        
        return response()->json($products);
    }
}
