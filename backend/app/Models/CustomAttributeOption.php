<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomAttributeOption extends Model
{
    use HasFactory;
    
    protected $table = 'custom_attribute_options';
    
    protected $fillable = [
        'attribute_id',
        'value',
        'label',
        'color_code',
        'position'
    ];
    
    protected $casts = [
        'position' => 'integer'
    ];
    
    // Relationships
    public function attribute()
    {
        return $this->belongsTo(CustomAttribute::class, 'attribute_id');
    }
}
