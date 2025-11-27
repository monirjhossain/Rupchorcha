<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Product\Repositories\ProductRepository;

class CategoryController extends Controller
{
    protected $categoryRepository;
    protected $productRepository;

    public function __construct(
        CategoryRepository $categoryRepository,
        ProductRepository $productRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productRepository = $productRepository;
    }

    /**
     * Display all categories
     */
    public function index()
    {
        $categories = $this->categoryRepository->getVisibleCategoryTree(
            core()->getCurrentChannel()->root_category_id
        );

        return view('frontend.categories.index', compact('categories'));
    }

    /**
     * Display category with products
     */
    public function show($slug)
    {
        $category = $this->categoryRepository->findBySlug($slug);

        if (!$category) {
            abort(404);
        }

        // Get products by category
        $products = $this->productRepository->getProductsFromCategory($category->id);

        // Get child categories
        $childCategories = $category->children;

        return view('frontend.categories.show', compact('category', 'products', 'childCategories'));
    }
}
