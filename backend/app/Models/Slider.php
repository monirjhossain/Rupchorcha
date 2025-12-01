<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'path',
        'content',
        'channel_id',
        'slider_path',
        'locale',
        'expired_at',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'channel_id' => 'integer',
        'expired_at' => 'datetime',
    ];

    /**
     * Scope for active sliders (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->whereNull('expired_at')
              ->orWhere('expired_at', '>', now());
        });
    }

    /**
     * Get full image URL
     */
    public function getImageUrlAttribute()
    {
        if (str_starts_with($this->path, 'http')) {
            return $this->path;
        }
        return asset('storage/' . $this->path);
    }
}
