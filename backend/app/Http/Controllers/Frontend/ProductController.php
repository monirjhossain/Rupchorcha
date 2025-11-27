<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Repositories\ProductReviewRepository;

class ProductController extends Controller
{
    protected $productRepository;
    protected $productReviewRepository;

    public function __construct(
        ProductRepository $productRepository,
        ProductReviewRepository $productReviewRepository
    ) {
        $this->productRepository = $productRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * Display product listing
     */
    public function index()
    {
        $products = $this->productRepository->getAll();

        return view('frontend.products.index', compact('products'));
    }

    /**
     * Display product details
     */
    public function show($slug)
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            abort(404);
        }

        // Get related products
        $relatedProducts = $this->productRepository->getRelatedProducts($product);

        // Get reviews
        $reviews = $this->productReviewRepository->getProductReviews($product->id);

        return view('frontend.products.show', compact('product', 'relatedProducts', 'reviews'));
    }

    /**
     * Search products
     */
    public function search(Request $request)
    {
        $term = $request->get('q');
        $products = $this->productRepository->searchProducts($term);

        return view('frontend.products.search', compact('products', 'term'));
    }

    /**
     * Store product review
     */
    public function storeReview(Request $request, $productId)
    {
        $request->validate([
            'rating' => 'required|integer|between:1,5',
            'title' => 'required|string|max:255',
            'comment' => 'required|string',
        ]);

        $review = $this->productReviewRepository->create([
            'product_id' => $productId,
            'customer_id' => auth()->guard('customer')->id(),
            'name' => auth()->guard('customer')->user()->name,
            'rating' => $request->rating,
            'title' => $request->title,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Review submitted successfully!');
    }
}
