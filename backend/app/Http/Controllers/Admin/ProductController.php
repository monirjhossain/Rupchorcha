<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display product listing
     */
    public function index(Request $request)
    {
        $query = Product::with('categories', 'images');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(20);
        $categories = Category::active()->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show create product form
     */
    public function create()
    {
        $categories = Category::active()->get();

        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'sku' => 'required|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'description' => 'nullable',
            'categories' => 'required|array',
            'images.*' => 'nullable|image|max:2048',
        ]);

        try {
            // Create product
            $product = Product::create([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'special_price' => $request->special_price,
                'quantity' => $request->quantity,
                'weight' => $request->weight,
                'status' => $request->has('status') ? 1 : 0,
                'featured' => $request->has('featured') ? 1 : 0,
                'new' => $request->has('new') ? 1 : 0,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
            ]);

            // Attach categories
            $product->categories()->sync($request->categories);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => '/storage/' . $path,
                        'position' => $index,
                    ]);
                }
            }

            return redirect()->route('admin.products.index')
                ->with('success', 'Product created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit product form
     */
    public function edit($id)
    {
        $product = Product::with('categories', 'images')->findOrFail($id);
        $categories = Category::active()->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'required|max:255',
            'sku' => 'required|unique:products,sku,' . $id,
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:0',
            'categories' => 'required|array',
            'images.*' => 'nullable|image|max:2048',
        ]);

        try {
            // Update product
            $product->update([
                'name' => $request->name,
                'slug' => Str::slug($request->name),
                'sku' => $request->sku,
                'description' => $request->description,
                'short_description' => $request->short_description,
                'price' => $request->price,
                'special_price' => $request->special_price,
                'quantity' => $request->quantity,
                'weight' => $request->weight,
                'status' => $request->has('status') ? 1 : 0,
                'featured' => $request->has('featured') ? 1 : 0,
                'new' => $request->has('new') ? 1 : 0,
                'meta_title' => $request->meta_title,
                'meta_description' => $request->meta_description,
                'meta_keywords' => $request->meta_keywords,
            ]);

            // Update categories
            $product->categories()->sync($request->categories);

            // Handle new image uploads
            if ($request->hasFile('images')) {
                $currentMaxPosition = $product->images()->max('position') ?? -1;
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'path' => '/storage/' . $path,
                        'position' => $currentMaxPosition + $index + 1,
                    ]);
                }
            }

            return redirect()->back()->with('success', 'Product updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Delete product images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $image->path));
            }
            
            $product->delete();

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
                $product = Product::find($productId);
                if ($product) {
                    // Delete images
                    foreach ($product->images as $image) {
                        Storage::disk('public')->delete(str_replace('/storage/', '', $image->path));
                    }
                    $product->delete();
                }
            }

            return redirect()->back()->with('success', 'Products deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete product image
     */
    public function deleteImage($id)
    {
        try {
            $image = ProductImage::findOrFail($id);
            Storage::disk('public')->delete(str_replace('/storage/', '', $image->path));
            $image->delete();

            return response()->json(['success' => true, 'message' => 'Image deleted successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
