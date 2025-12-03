<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    use HasFactory;
    
    protected $table = 'custom_product_reviews';
    
    protected $fillable = [
        'product_id',
        'customer_id',
        'customer_name',
        'customer_email',
        'rating',
        'title',
        'comment',
        'status'
    ];
    
    protected $casts = [
        'rating' => 'integer'
    ];
    
    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
    
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }
}
