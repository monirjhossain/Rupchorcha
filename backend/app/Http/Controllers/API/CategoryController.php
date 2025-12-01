<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Get all categories
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Category::with(['parent', 'children'])
                ->where('status', 1)
                ->withCount('products');
            
            // Filter by parent
            if ($request->has('parent_id')) {
                if ($request->parent_id === '0' || $request->parent_id === 0) {
                    $query->whereNull('parent_id');
                } elseif ($request->parent_id) {
                    $query->where('parent_id', $request->parent_id);
                }
            }
            
            // Root categories only
            if ($request->has('root_only') && $request->root_only) {
                $query->whereNull('parent_id');
            }
            
            $categories = $query->orderBy('position', 'asc')
                ->orderBy('name', 'asc')
                ->get();
            
            // Transform data
            $categories = $categories->map(function($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image ? asset('storage/' . $category->image) : null,
                    'parent_id' => $category->parent_id,
                    'position' => $category->position,
                    'products_count' => $category->products_count,
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name,
                        'slug' => $category->parent->slug,
                    ] : null,
                    'children' => $category->children->map(function($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                        ];
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch categories',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get single category by ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $category = Category::with(['parent', 'children'])
                ->where('status', 1)
                ->withCount('products')
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'image' => $category->image ? asset('storage/' . $category->image) : null,
                    'parent_id' => $category->parent_id,
                    'position' => $category->position,
                    'products_count' => $category->products_count,
                    'parent' => $category->parent ? [
                        'id' => $category->parent->id,
                        'name' => $category->parent->name,
                        'slug' => $category->parent->slug,
                    ] : null,
                    'children' => $category->children->map(function($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'products_count' => $child->products->count(),
                        ];
                    }),
                    'breadcrumbs' => $category->breadcrumbs->map(function($crumb) {
                        return [
                            'id' => $crumb->id,
                            'name' => $crumb->name,
                            'slug' => $crumb->slug,
                        ];
                    }),
                    'meta_title' => $category->meta_title,
                    'meta_description' => $category->meta_description,
                ],
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get products by category
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function products(Request $request, $id)
    {
        try {
            $category = Category::where('status', 1)->findOrFail($id);
            
            $query = $category->products()
                ->where('status', 1)
                ->with(['categories', 'images']);
            
            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            if (in_array($sortBy, ['name', 'price', 'created_at'])) {
                $query->orderBy($sortBy, $sortOrder);
            }
            
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
                    'featured' => (bool) $product->featured,
                    'new' => (bool) $product->new,
                    'image' => $product->images->first() ? asset('storage/' . $product->images->first()->path) : null,
                    'images' => $product->images->map(function($image) {
                        return asset('storage/' . $image->path);
                    }),
                ];
            });

            return response()->json([
                'success' => true,
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ],
                'data' => $products,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch category products',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
