<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Velocity\Repositories\VelocityMetadataRepository;

class HomeController extends Controller
{
    protected $productRepository;
    protected $categoryRepository;
    protected $velocityMetadataRepository;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        VelocityMetadataRepository $velocityMetadataRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->velocityMetadataRepository = $velocityMetadataRepository;
    }

    /**
     * Display homepage
     */
    public function index()
    {
        // Get featured products
        $featuredProducts = $this->productRepository->getFeaturedProducts();
        
        // Get new products
        $newProducts = $this->productRepository->getNewProducts();
        
        // Get categories
        $categories = $this->categoryRepository->getVisibleCategoryTree(
            core()->getCurrentChannel()->root_category_id
        );
        
        // Get velocity metadata
        $velocityMetaData = $this->velocityMetadataRepository->findWhere([
            'channel_id' => core()->getCurrentChannel()->id,
            'locale' => app()->getLocale(),
        ])->first();

        return view('frontend.home', compact(
            'featuredProducts',
            'newProducts',
            'categories',
            'velocityMetaData'
        ));
    }
}
