<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Get available shipping methods from database
     */
    public function getMethods(Request $request)
    {
        $shippingMethods = ShippingSetting::getActiveMethods()->map(function($method) {
            return [
                'id' => $method->method_code,
                'code' => $method->method_code,
                'title' => $method->method_name,
                'description' => $method->description,
                'price' => (float) $method->price,
                'delivery_time' => $method->delivery_time_min . '-' . $method->delivery_time_max . ' days'
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $shippingMethods
        ]);
    }

    /**
     * Calculate shipping cost
     */
    public function calculateShipping(Request $request)
    {
        $request->validate([
            'method' => 'required|string',
            'subtotal' => 'required|numeric'
        ]);

        $methodCode = $request->method;
        $subtotal = $request->subtotal;

        $freeShippingThreshold = (float) GeneralSetting::get('free_shipping_threshold', 3000);
        $method = ShippingSetting::getByCode($methodCode);

        if (!$method || !$method->active) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid shipping method'
            ], 400);
        }

        // Calculate shipping cost
        $shippingCost = 0;
        if ($subtotal >= $freeShippingThreshold) {
            $shippingCost = 0;
        } else {
            $shippingCost = (float) $method->price;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'shipping_cost' => $shippingCost,
                'free_shipping' => $subtotal >= $freeShippingThreshold,
                'free_shipping_threshold' => $freeShippingThreshold
            ]
        ]);
    }
}
