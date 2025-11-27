<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductInventoryRepository;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;

class ProductController extends Controller
{
    protected $productRepository;
    protected $productInventoryRepository;
    protected $attributeFamilyRepository;

    public function __construct(
        ProductRepository $productRepository,
        ProductInventoryRepository $productInventoryRepository,
        AttributeFamilyRepository $attributeFamilyRepository
    ) {
        $this->middleware('auth:admin');
        $this->productRepository = $productRepository;
        $this->productInventoryRepository = $productInventoryRepository;
        $this->attributeFamilyRepository = $attributeFamilyRepository;
    }

    /**
     * Display product listing
     */
    public function index()
    {
        $products = $this->productRepository->paginate(20);

        return view('admin.products.index', compact('products'));
    }

    /**
     * Show create product form
     */
    public function create()
    {
        $attributeFamilies = $this->attributeFamilyRepository->all();

        return view('admin.products.create', compact('attributeFamilies'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'attribute_family_id' => 'required',
            'sku' => 'required|unique:products,sku',
        ]);

        try {
            $product = $this->productRepository->create($request->all());

            return redirect()->route('admin.products.edit', $product->id)
                ->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $product = $this->productRepository->findOrFail($id);
        $attributeFamilies = $this->attributeFamilyRepository->all();

        return view('admin.products.edit', compact('product', 'attributeFamilies'));
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        try {
            $this->productRepository->update($request->all(), $id);

            return redirect()->back()->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        try {
            $this->productRepository->delete($id);

            return redirect()->route('admin.products.index')
                ->with('success', 'Product deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mass delete products
     */
    public function massDestroy(Request $request)
    {
        try {
            $productIds = explode(',', $request->input('indexes'));
            
            foreach ($productIds as $productId) {
                $this->productRepository->delete($productId);
            }

            return redirect()->back()->with('success', 'Products deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
