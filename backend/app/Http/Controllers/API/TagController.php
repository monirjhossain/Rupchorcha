<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Get all tags
     */
    public function index(Request $request)
    {
        try {
            $query = Tag::query();
            
            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            } else {
                // By default, return only active tags
                $query->active();
            }
            
            // Search by name
            if ($request->has('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }
            
            // Get all or paginated
            if ($request->has('all') && $request->all == 'true') {
                $tags = $query->orderBy('name')->get();
                
                $formattedTags = $tags->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                        'status' => $tag->status,
                        'products_count' => $tag->products()->count(),
                    ];
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $formattedTags
                ]);
            } else {
                $perPage = $request->get('per_page', 15);
                $tags = $query->orderBy('name')->paginate($perPage);
                
                $formattedTags = $tags->getCollection()->map(function ($tag) {
                    return [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                        'status' => $tag->status,
                        'products_count' => $tag->products()->count(),
                    ];
                });
                
                return response()->json([
                    'success' => true,
                    'data' => $formattedTags,
                    'pagination' => [
                        'total' => $tags->total(),
                        'per_page' => $tags->perPage(),
                        'current_page' => $tags->currentPage(),
                        'last_page' => $tags->lastPage(),
                        'from' => $tags->firstItem(),
                        'to' => $tags->lastItem(),
                    ]
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tags',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single tag with details
     */
    public function show($id)
    {
        try {
            $tag = Tag::findOrFail($id);
            
            $formattedTag = [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
                'status' => $tag->status,
                'products_count' => $tag->products()->count(),
                'created_at' => $tag->created_at,
                'updated_at' => $tag->updated_at,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $formattedTag
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tag not found'
            ], 404);
        }
    }
    
    /**
     * Get products by tag
     */
    public function products($id, Request $request)
    {
        try {
            $tag = Tag::findOrFail($id);
            
            $perPage = $request->get('per_page', 12);
            
            $products = $tag->products()
                ->with(['images', 'categories', 'brands'])
                ->where('status', 1)
                ->paginate($perPage);
            
            $formattedProducts = $products->getCollection()->map(function ($product) {
                $firstImage = $product->images->first();
                
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'special_price' => $product->special_price,
                    'cost_price' => $product->cost_price,
                    'status' => $product->status,
                    'image' => $firstImage ? asset('storage/' . $firstImage->path) : null,
                    'categories' => $product->categories->pluck('name'),
                    'brands' => $product->brands->pluck('name'),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'tag' => [
                        'id' => $tag->id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'color' => $tag->color,
                    ],
                    'products' => $formattedProducts
                ],
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'from' => $products->firstItem(),
                    'to' => $products->lastItem(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
