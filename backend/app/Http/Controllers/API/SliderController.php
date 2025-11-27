<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Webkul\Core\Repositories\SliderRepository;

class SliderController extends Controller
{
    protected $sliderRepository;

    public function __construct(SliderRepository $sliderRepository)
    {
        $this->sliderRepository = $sliderRepository;
    }

    public function index()
    {
        try {
            $channelId = core()->getCurrentChannel()->id;
            
            // Directly query sliders instead of using repository method
            $sliders = \Webkul\Core\Models\Slider::where('channel_id', $channelId)
                ->orderBy('sort_order')
                ->get();
            
            $data = $sliders->map(function($slider) {
                $imagePath = null;
                
                // Check for path field
                if (!empty($slider->path)) {
                    $imagePath = url('storage/' . $slider->path);
                } elseif (!empty($slider->image_url)) {
                    $imagePath = $slider->image_url;
                } else {
                    $imagePath = 'https://via.placeholder.com/1400x400/e8e8e8/666?text=Upload+Slider+Image';
                }
                
                return [
                    'id' => $slider->id,
                    'title' => $slider->title ?? 'BUY',
                    'leftProduct' => $slider->title ?? 'Premium Product',
                    'rightProduct' => 'Special Offer',
                    'offer' => 'GET FREE',
                    'freeProduct' => $slider->content ?? 'Limited Time Offer',
                    'image' => $imagePath,
                ];
            });
            
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage()
            ]);
        }
    }
}
