<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Get all brands
     */
    public function index()
    {
        try {
            $brands = Brand::where('status', 1)
                ->orderBy('position', 'asc')
                ->get()
                ->map(function ($brand) {
                    return [
                        'id' => $brand->id,
                        'name' => $brand->name,
                        'slug' => $brand->slug,
                        'description' => $brand->description,
                        'logo' => $brand->logo_url,
                        'website' => $brand->website,
                    ];
                });
            
            return response()->json([
                'success' => true,
                'data' => $brands
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch brands',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single brand
     */
    public function show($id)
    {
        try {
            $brand = Brand::where('status', 1)->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'logo' => $brand->logo_url,
                    'website' => $brand->website,
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Brand not found'
            ], 404);
        }
    }
    
    /**
     * Get products by brand
     */
    public function products($id, Request $request)
    {
        try {
            $brand = Brand::where('status', 1)->findOrFail($id);
            
            $perPage = $request->get('per_page', 12);
            
            $products = $brand->products()
                ->where('status', 1)
                ->with(['images', 'categories'])
                ->paginate($perPage);
            
            // Transform products data
            $products->getCollection()->transform(function($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'special_price' => $product->special_price ? (float) $product->special_price : null,
                    'images' => $product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->path),
                            'path' => $image->path,
                        ];
                    }),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $products
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
