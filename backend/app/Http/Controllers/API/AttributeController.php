<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Product\Models\Product;

class AttributeController extends Controller
{
    /**
     * Get all attributes with their options
     */
    public function index(Request $request)
    {
        try {
            $query = Attribute::with(['options', 'options.translations']);
            
            // Filter by filterable attributes only
            if ($request->has('filterable') && $request->filterable) {
                $query->where('is_filterable', 1);
            }
            
            // Filter by visible on front
            if ($request->has('visible') && $request->visible) {
                $query->where('is_visible_on_front', 1);
            }
            
            // Filter by attribute code
            if ($request->has('code')) {
                $query->where('code', $request->code);
            }
            
            $perPage = $request->get('per_page', 50);
            $attributes = $query->orderBy('position')->paginate($perPage);
            
            $formattedAttributes = $attributes->getCollection()->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'code' => $attribute->code,
                    'admin_name' => $attribute->admin_name,
                    'type' => $attribute->type,
                    'position' => $attribute->position,
                    'is_required' => $attribute->is_required,
                    'is_unique' => $attribute->is_unique,
                    'is_filterable' => $attribute->is_filterable,
                    'is_configurable' => $attribute->is_configurable,
                    'is_visible_on_front' => $attribute->is_visible_on_front,
                    'options' => $attribute->options->map(function ($option) {
                        $translation = $option->translations->first();
                        return [
                            'id' => $option->id,
                            'admin_name' => $option->admin_name,
                            'label' => $translation ? $translation->label : $option->admin_name,
                            'swatch_value' => $option->swatch_value,
                            'sort_order' => $option->sort_order,
                        ];
                    })->sortBy('sort_order')->values()
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedAttributes,
                'pagination' => [
                    'total' => $attributes->total(),
                    'per_page' => $attributes->perPage(),
                    'current_page' => $attributes->currentPage(),
                    'last_page' => $attributes->lastPage(),
                    'from' => $attributes->firstItem(),
                    'to' => $attributes->lastItem(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attributes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get single attribute with options
     */
    public function show($id)
    {
        try {
            $attribute = Attribute::with(['options', 'options.translations'])->findOrFail($id);
            
            $formattedAttribute = [
                'id' => $attribute->id,
                'code' => $attribute->code,
                'admin_name' => $attribute->admin_name,
                'type' => $attribute->type,
                'position' => $attribute->position,
                'is_required' => $attribute->is_required,
                'is_unique' => $attribute->is_unique,
                'validation' => $attribute->validation,
                'is_filterable' => $attribute->is_filterable,
                'is_configurable' => $attribute->is_configurable,
                'is_visible_on_front' => $attribute->is_visible_on_front,
                'is_user_defined' => $attribute->is_user_defined,
                'swatch_type' => $attribute->swatch_type,
                'options' => $attribute->options->map(function ($option) {
                    $translation = $option->translations->first();
                    return [
                        'id' => $option->id,
                        'admin_name' => $option->admin_name,
                        'label' => $translation ? $translation->label : $option->admin_name,
                        'swatch_value' => $option->swatch_value,
                        'sort_order' => $option->sort_order,
                    ];
                })->sortBy('sort_order')->values()
            ];
            
            return response()->json([
                'success' => true,
                'data' => $formattedAttribute
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found'
            ], 404);
        }
    }
    
    /**
     * Get products by attribute option
     */
    public function products($optionId, Request $request)
    {
        try {
            $option = AttributeOption::findOrFail($optionId);
            
            $perPage = $request->get('per_page', 12);
            
            // Get products with this attribute option value
            $products = Product::whereHas('attribute_values', function ($query) use ($optionId) {
                $query->where('integer_value', $optionId)
                      ->orWhere('text_value', $optionId);
            })
            ->with(['images', 'inventories', 'categories'])
            ->where('status', 1)
            ->paginate($perPage);
            
            $formattedProducts = $products->getCollection()->map(function ($product) {
                $firstImage = $product->images->first();
                $inventory = $product->inventories->first();
                
                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'url_key' => $product->url_key,
                    'price' => $product->price,
                    'special_price' => $product->special_price,
                    'status' => $product->status,
                    'image' => $firstImage ? asset('storage/' . $firstImage->path) : null,
                    'in_stock' => $inventory && $inventory->qty > 0,
                    'qty' => $inventory ? $inventory->qty : 0,
                    'categories' => $product->categories->pluck('name'),
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
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
