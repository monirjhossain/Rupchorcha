<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProductBundle;
use App\Models\Product;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    /**
     * Get all product bundles (frequently bought together)
     */
    public function index(Request $request)
    {
        try {
            $query = ProductBundle::with(['product', 'bundleProduct']);
            
            // Filter by product ID
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            
            // Search by product name
            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('product', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('bundleProduct', function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            }
            
            $perPage = $request->get('per_page', 15);
            $bundles = $query->ordered()->paginate($perPage);
            
            $formattedBundles = $bundles->getCollection()->map(function ($bundle) {
                return [
                    'id' => $bundle->id,
                    'product' => [
                        'id' => $bundle->product->id,
                        'name' => $bundle->product->name,
                        'sku' => $bundle->product->sku,
                        'price' => $bundle->product->price,
                        'image' => $bundle->product->images->first() 
                            ? asset('storage/' . $bundle->product->images->first()->path) 
                            : null,
                    ],
                    'bundle_product' => [
                        'id' => $bundle->bundleProduct->id,
                        'name' => $bundle->bundleProduct->name,
                        'sku' => $bundle->bundleProduct->sku,
                        'price' => $bundle->bundleProduct->price,
                        'image' => $bundle->bundleProduct->images->first() 
                            ? asset('storage/' . $bundle->bundleProduct->images->first()->path) 
                            : null,
                    ],
                    'discount_percentage' => $bundle->discount_percentage,
                    'discounted_price' => $bundle->bundleProduct->price * (1 - $bundle->discount_percentage / 100),
                    'total_price' => $bundle->product->price + ($bundle->bundleProduct->price * (1 - $bundle->discount_percentage / 100)),
                    'position' => $bundle->position,
                    'created_at' => $bundle->created_at,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedBundles,
                'pagination' => [
                    'total' => $bundles->total(),
                    'per_page' => $bundles->perPage(),
                    'current_page' => $bundles->currentPage(),
                    'last_page' => $bundles->lastPage(),
                    'from' => $bundles->firstItem(),
                    'to' => $bundles->lastItem(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch bundles',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single bundle with details
     */
    public function show($id)
    {
        try {
            $bundle = ProductBundle::with(['product', 'product.images', 'bundleProduct', 'bundleProduct.images'])
                ->findOrFail($id);
            
            $formattedBundle = [
                'id' => $bundle->id,
                'product' => [
                    'id' => $bundle->product->id,
                    'name' => $bundle->product->name,
                    'sku' => $bundle->product->sku,
                    'price' => $bundle->product->price,
                    'description' => $bundle->product->description,
                    'image' => $bundle->product->images->first() 
                        ? asset('storage/' . $bundle->product->images->first()->path) 
                        : null,
                    'status' => $bundle->product->status,
                ],
                'bundle_product' => [
                    'id' => $bundle->bundleProduct->id,
                    'name' => $bundle->bundleProduct->name,
                    'sku' => $bundle->bundleProduct->sku,
                    'price' => $bundle->bundleProduct->price,
                    'description' => $bundle->bundleProduct->description,
                    'image' => $bundle->bundleProduct->images->first() 
                        ? asset('storage/' . $bundle->bundleProduct->images->first()->path) 
                        : null,
                    'status' => $bundle->bundleProduct->status,
                ],
                'discount_percentage' => $bundle->discount_percentage,
                'discounted_price' => $bundle->bundleProduct->price * (1 - $bundle->discount_percentage / 100),
                'savings' => $bundle->bundleProduct->price * ($bundle->discount_percentage / 100),
                'total_price' => $bundle->product->price + ($bundle->bundleProduct->price * (1 - $bundle->discount_percentage / 100)),
                'position' => $bundle->position,
                'created_at' => $bundle->created_at,
                'updated_at' => $bundle->updated_at,
            ];
            
            return response()->json([
                'success' => true,
                'data' => $formattedBundle
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bundle not found'
            ], 404);
        }
    }
    
    /**
     * Get bundles for a specific product (frequently bought together)
     */
    public function getProductBundles($productId)
    {
        try {
            $product = Product::findOrFail($productId);
            
            $bundles = ProductBundle::with(['bundleProduct', 'bundleProduct.images'])
                ->where('product_id', $productId)
                ->ordered()
                ->get();
            
            $formattedBundles = $bundles->map(function ($bundle) {
                return [
                    'id' => $bundle->id,
                    'product' => [
                        'id' => $bundle->bundleProduct->id,
                        'name' => $bundle->bundleProduct->name,
                        'sku' => $bundle->bundleProduct->sku,
                        'price' => $bundle->bundleProduct->price,
                        'image' => $bundle->bundleProduct->images->first() 
                            ? asset('storage/' . $bundle->bundleProduct->images->first()->path) 
                            : null,
                        'status' => $bundle->bundleProduct->status,
                    ],
                    'discount_percentage' => $bundle->discount_percentage,
                    'discounted_price' => $bundle->bundleProduct->price * (1 - $bundle->discount_percentage / 100),
                    'savings' => $bundle->bundleProduct->price * ($bundle->discount_percentage / 100),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => [
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'bundles' => $formattedBundles
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
