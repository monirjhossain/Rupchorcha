<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBundle extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id',
        'bundle_product_id',
        'discount_percentage',
        'position'
    ];
    
    protected $casts = [
        'discount_percentage' => 'integer',
        'position' => 'integer'
    ];
    
    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    public function bundleProduct()
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }
    
    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
}
