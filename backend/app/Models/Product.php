<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'description',
        'short_description',
        'price',
        'cost_price',
        'special_price',
        'quantity',
        'weight',
        'status',
        'featured',
        'new',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'special_price' => 'decimal:2',
        'quantity' => 'integer',
        'status' => 'boolean',
        'featured' => 'boolean',
        'new' => 'boolean',
    ];

    /**
     * Get product categories
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_categories', 'product_id', 'category_id');
    }

    /**
     * Get product images
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    /**
     * Get product brands
     */
    public function brands()
    {
        return $this->belongsToMany(Brand::class, 'brand_product');
    }
    
    /**
     * Get product tags
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }
    
    /**
     * Get product bundles (frequently bought together)
     */
    public function bundles()
    {
        return $this->hasMany(ProductBundle::class, 'product_id');
    }
    
    /**
     * Get bundled products
     */
    public function bundledProducts()
    {
        return $this->belongsToMany(Product::class, 'product_bundles', 'product_id', 'bundle_product_id')
                    ->withPivot('discount_percentage', 'position')
                    ->orderBy('product_bundles.position');
    }
    
    /**
     * Get product reviews
     */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class);
    }
    
    /**
     * Get approved reviews only
     */
    public function approvedReviews()
    {
        return $this->hasMany(ProductReview::class)->where('status', 'approved');
    }

    /**
     * Get main product image
     */
    public function getMainImageAttribute()
    {
        $firstImage = $this->images()->first();
        return $firstImage ? $firstImage->path : '/images/placeholder.png';
    }

    /**
     * Check if product is in stock
     */
    public function isInStock()
    {
        return $this->quantity > 0;
    }

    /**
     * Get display price (special price if available, otherwise regular price)
     */
    public function getDisplayPriceAttribute()
    {
        return $this->special_price ?? $this->price;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope for featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', 1);
    }

    /**
     * Scope for new products
     */
    public function scopeNew($query)
    {
        return $query->where('new', 1);
    }
}
