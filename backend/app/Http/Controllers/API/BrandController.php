<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    /**
     * Get all brands
     */
    public function index()
    {
        try {
            // Bagisto doesn't have built-in brands table
            // We'll use attribute options for brands
            $brandAttribute = \Webkul\Attribute\Models\Attribute::where('code', 'brand')->first();
            
            if (!$brandAttribute) {
                return response()->json([
                    'success' => false,
                    'message' => 'Brand attribute not found',
                    'data' => []
                ]);
            }
            
            $brands = \Webkul\Attribute\Models\AttributeOption::where('attribute_id', $brandAttribute->id)
                ->with('translations')
                ->get()
                ->map(function ($brand) {
                    $translation = $brand->translations->first();
                    return [
                        'id' => $brand->id,
                        'name' => $translation ? $translation->label : 'Unknown',
                        'admin_name' => $brand->admin_name,
                        'swatch_value' => $brand->swatch_value,
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
            $brand = \Webkul\Attribute\Models\AttributeOption::with('translations')
                ->findOrFail($id);
            
            $translation = $brand->translations->first();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $brand->id,
                    'name' => $translation ? $translation->label : 'Unknown',
                    'admin_name' => $brand->admin_name,
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
            $perPage = $request->get('per_page', 12);
            $page = $request->get('page', 1);
            
            // Get products with this brand attribute value
            $products = \Webkul\Product\Models\Product::whereHas('attribute_values', function ($query) use ($id) {
                $query->where('integer_value', $id)
                      ->orWhere('text_value', $id);
            })
            ->with('images', 'inventories')
            ->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $products->items(),
                'pagination' => [
                    'total' => $products->total(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
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
