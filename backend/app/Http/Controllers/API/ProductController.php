<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Product\Repositories\ProductRepository;

class ProductController extends Controller
{
    /**
     * ProductRepository object
     *
     * @var \Webkul\Product\Repositories\ProductRepository
     */
    protected $productRepository;

    /**
     * Create a new controller instance.
     *
     * @param  \Webkul\Product\Repositories\ProductRepository  $productRepository
     * @return void
     */
    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * Get all products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        try {
            $query = \Webkul\Product\Models\ProductFlat::query()
                ->where('locale', core()->getCurrentLocale()->code)
                ->where('channel', core()->getCurrentChannel()->code);
            
            // Price filter
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }
            
            // Category filter
            if ($request->has('categories') && !empty($request->categories)) {
                $categoryIds = explode(',', $request->categories);
                $query->whereHas('product.categories', function($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
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
                        $query->orderBy('product_id', 'desc');
                }
            } else {
                $query->orderBy('product_id', 'desc');
            }
            
            $products = $query->with(['product.images'])->paginate($perPage);
            
            // Format products with images
            $formattedProducts = collect($products->items())->map(function($flat) {
                $data = [
                    'id' => $flat->product_id,
                    'name' => $flat->name,
                    'price' => $flat->price,
                    'special_price' => $flat->special_price,
                    'description' => $flat->description,
                    'short_description' => $flat->short_description,
                    'url_key' => $flat->url_key,
                    'images' => []
                ];
                
                if ($flat->product && $flat->product->images) {
                    $data['images'] = $flat->product->images->map(function($image) {
                        return [
                            'id' => $image->id,
                            'url' => asset('storage/' . $image->path),
                            'path' => $image->path
                        ];
                    });
                }
                
                return $data;
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $formattedProducts,
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'per_page' => $products->perPage(),
                    'total' => $products->total(),
                ],
                'message' => 'Products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single product by ID
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        // Use ProductFlat for consistent data structure
        $flat = \Webkul\Product\Models\ProductFlat::where('product_id', $id)
            ->with(['product.images'])
            ->first();

        if (!$flat) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Format product data same as index method
        $data = [
            'id' => $flat->product_id,
            'name' => $flat->name,
            'price' => $flat->price,
            'description' => $flat->description,
            'short_description' => $flat->short_description,
            'sku' => $flat->sku,
            'url_key' => $flat->url_key,
        ];

        // Format images with full URL
        if ($flat->product && $flat->product->images) {
            $data['images'] = $flat->product->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => asset('storage/' . $image->path),
                    'path' => $image->path
                ];
            })->toArray();
        } else {
            $data['images'] = [];
        }

        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Product retrieved successfully'
        ]);
    }

    /**
     * Search products
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $term = $request->get('q');
        
        $products = $this->productRepository->searchProducts($term);

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Search results retrieved successfully'
        ]);
    }
}
