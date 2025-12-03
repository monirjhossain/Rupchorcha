<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'slug',
        'color',
        'status'
    ];
    
    protected $casts = [
        'status' => 'boolean'
    ];
    
    // Relationships
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    // Automatically generate slug if not provided
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }
}
