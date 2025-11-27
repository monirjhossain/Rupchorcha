<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomCategory extends Model
{
    protected $table = 'custom_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'position',
        'status',
        'image'
    ];

    protected $casts = [
        'position' => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Get the parent category
     */
    public function parent()
    {
        return $this->belongsTo(CustomCategory::class, 'parent_id');
    }

    /**
     * Get the child categories
     */
    public function children()
    {
        return $this->hasMany(CustomCategory::class, 'parent_id');
    }

    /**
     * Get the products for the category
     */
    public function products()
    {
        return $this->hasMany(CustomProduct::class, 'category_id');
    }

    /**
     * Scope a query to only include active categories
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
