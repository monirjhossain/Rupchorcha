<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CustomAttribute extends Model
{
    use HasFactory;
    
    protected $table = 'custom_attributes';
    
    protected $fillable = [
        'name',
        'slug',
        'type',
        'is_filterable',
        'is_required',
        'position',
        'status'
    ];
    
    protected $casts = [
        'is_filterable' => 'boolean',
        'is_required' => 'boolean',
        'status' => 'boolean',
        'position' => 'integer'
    ];
    
    // Relationships
    public function options()
    {
        return $this->hasMany(CustomAttributeOption::class, 'attribute_id')->orderBy('position');
    }
    
    public function values()
    {
        return $this->hasMany(CustomAttributeValue::class, 'attribute_id');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }
    
    // Automatically generate slug if not provided
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($attribute) {
            if (empty($attribute->slug)) {
                $attribute->slug = Str::slug($attribute->name);
            }
        });
    }
}
