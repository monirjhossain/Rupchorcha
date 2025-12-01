<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_code',
        'method_name',
        'description',
        'price',
        'delivery_time_min',
        'delivery_time_max',
        'active',
        'sort_order'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
        'delivery_time_min' => 'integer',
        'delivery_time_max' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get active shipping methods
     */
    public static function getActiveMethods()
    {
        return self::where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Get method by code
     */
    public static function getByCode($code)
    {
        return self::where('method_code', $code)->first();
    }
}
