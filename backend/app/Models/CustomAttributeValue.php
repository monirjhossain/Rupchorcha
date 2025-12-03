<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomAttributeValue extends Model
{
    use HasFactory;
    
    protected $table = 'custom_attribute_values';
    
    protected $fillable = [
        'product_id',
        'attribute_id',
        'value',
        'option_id'
    ];
    
    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function attribute()
    {
        return $this->belongsTo(CustomAttribute::class, 'attribute_id');
    }
    
    public function option()
    {
        return $this->belongsTo(CustomAttributeOption::class, 'option_id');
    }
}
