<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Get all products with filters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Product::with(['categories', 'images'])
                ->where('status', 1);
            
            // Search
            if ($request->has('search') && $request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
                });
            }
            
            // Category filter
            if ($request->has('category_id') && $request->category_id) {
                $query->whereHas('categories', function($q) use ($request) {
                    $q->where('categories.id', $request->category_id);
                });
            }
            
            // Price filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Featured products
            if ($request->has('featured') && $request->featured) {
                $query->where('featured', 1);
            }
            
            // New products
            if ($request->has('new') && $request->new) {
                $query->where('new', 1);
            }
            
            // Sorting
            if ($request->has('sort')) {
                switch ($request->sort) {
                    case 'price_low':
                        $query->orderBy('price', 'asc');
                        break;
                    case 'price_high':
                        $query->orderBy('price', 'desc');
                        break;
                    case 'name_asc':
                        $query->orderBy('name', 'asc');
                        break;
                    case 'name_desc':
                        $query->orderBy('name', 'desc');
                        break;
                    case 'newest':
                        $query->orderBy('created_at', 'desc');
                        break;
                    default:
                        $query->orderBy('created_at', 'desc');
                }
            } else {
                $query->orderBy('created_at', 'desc');
            }
            
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            // Transform data for API
            $products->getCollection()->transform(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'price' => (float) $product->price,
                    'special_price' => $product->special_price ? (float) $product->special_price : null,
                    'display_price' => (float) $product->display_price,
                    'quantity' => $product->quantity,
                    'weight' => $product->weight ? (float) $product->weight : null,
                    'status' => (bool) $product->status,
                    'featured' => (bool) $product->featured,
                    'new' => (bool) $product->new,
                    'in_stock' => $product->isInStock(),
                    'images' => $product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->path),
                            'path' => $image->path,
                            'position' => $image->position,
                        ];
                    }),
                    'categories' => $product->categories->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                        ];
                    }),
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
        ]);
        
        try {
            $query = Product::with(['categories', 'images'])
                ->where('status', 1)
                ->where(function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->q . '%')
                      ->orWhere('description', 'like', '%' . $request->q . '%')
                      ->orWhere('short_description', 'like', '%' . $request->q . '%')
                      ->orWhere('sku', 'like', '%' . $request->q . '%');
                });
            
            $perPage = $request->get('per_page', 15);
            $products = $query->paginate($perPage);
            
            // Transform data
            $products->getCollection()->transform(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'special_price' => $product->special_price ? (float) $product->special_price : null,
                    'display_price' => (float) $product->display_price,
                    'quantity' => $product->quantity,
                    'in_stock' => $product->isInStock(),
                    'images' => $product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->path),
                            'path' => $image->path,
                        ];
                    }),
                    'categories' => $product->categories->pluck('name'),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $products,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single product details
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $product = Product::with(['categories', 'images'])
                ->where('status', 1)
                ->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'price' => (float) $product->price,
                    'special_price' => $product->special_price ? (float) $product->special_price : null,
                    'display_price' => (float) $product->display_price,
                    'quantity' => $product->quantity,
                    'weight' => $product->weight ? (float) $product->weight : null,
                    'status' => (bool) $product->status,
                    'featured' => (bool) $product->featured,
                    'new' => (bool) $product->new,
                    'in_stock' => $product->isInStock(),
                    'images' => $product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->path),
                            'path' => $image->path,
                            'position' => $image->position,
                        ];
                    }),
                    'categories' => $product->categories->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'description' => $category->description,
                        ];
                    }),
                    'meta_title' => $product->meta_title,
                    'meta_description' => $product->meta_description,
                    'meta_keywords' => $product->meta_keywords,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
