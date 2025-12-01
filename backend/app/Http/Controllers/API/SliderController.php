<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;

class SliderController extends Controller
{
    /**
     * Get all active sliders
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = Slider::active()
                ->orderBy('sort_order', 'asc')
                ->orderBy('created_at', 'desc');
            
            // Filter by locale
            if ($request->has('locale') && $request->locale) {
                $query->where('locale', $request->locale);
            } else {
                $query->where('locale', 'en'); // Default to English
            }
            
            $sliders = $query->get();
            
            // Transform data for frontend
            $data = $sliders->map(function($slider) {
                $imagePath = null;
                
                if (!empty($slider->path)) {
                    // Check if it's external URL
                    if (str_starts_with($slider->path, 'http')) {
                        $imagePath = $slider->path;
                    } else {
                        $imagePath = asset('storage/' . $slider->path);
                    }
                } else {
                    $imagePath = 'https://via.placeholder.com/1920x600/e8e8e8/666?text=Slider+Image';
                }
                
                return [
                    'id' => $slider->id,
                    'title' => $slider->title ?? 'Special Offer',
                    'content' => $slider->content ?? '',
                    'image' => $imagePath,
                    'link' => $slider->slider_path ?? '#',
                    'sort_order' => $slider->sort_order,
                    
                    // Legacy format support for existing frontend
                    'leftProduct' => $slider->title ?? 'Premium Product',
                    'rightProduct' => 'Special Offer',
                    'offer' => 'GET FREE',
                    'freeProduct' => $slider->content ?? 'Limited Time Offer',
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => 'Failed to fetch sliders',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
