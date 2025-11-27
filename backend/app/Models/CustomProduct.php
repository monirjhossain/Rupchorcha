<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomProduct extends Model
{
    protected $table = 'custom_products';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'special_price',
        'sku',
        'quantity',
        'status',
        'featured',
        'category_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'special_price' => 'decimal:2',
        'quantity' => 'integer',
        'status' => 'boolean',
        'featured' => 'boolean',
    ];

    /**
     * Get the category that owns the product
     */
    public function category()
    {
        return $this->belongsTo(CustomCategory::class, 'category_id');
    }

    /**
     * Get the images for the product
     */
    public function images()
    {
        return $this->hasMany(CustomProductImage::class, 'product_id');
    }

    /**
     * Scope a query to only include active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope a query to only include featured products
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
}
