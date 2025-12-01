<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class ShippingSettingsController extends Controller
{
    /**
     * Get all shipping settings
     */
    public function index()
    {
        $methods = ShippingSetting::orderBy('sort_order')->get();
        $freeShippingThreshold = GeneralSetting::get('free_shipping_threshold', 3000);

        return response()->json([
            'success' => true,
            'data' => [
                'shipping_methods' => $methods,
                'free_shipping_threshold' => $freeShippingThreshold
            ]
        ]);
    }

    /**
     * Get active shipping methods (for frontend)
     */
    public function getActiveMethods()
    {
        $methods = ShippingSetting::getActiveMethods();
        $freeShippingThreshold = GeneralSetting::get('free_shipping_threshold', 3000);

        return response()->json([
            'success' => true,
            'data' => [
                'methods' => $methods,
                'free_shipping_threshold' => $freeShippingThreshold
            ]
        ]);
    }

    /**
     * Update shipping method
     */
    public function updateMethod(Request $request, $id)
    {
        $request->validate([
            'method_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'delivery_time_min' => 'required|integer|min:1',
            'delivery_time_max' => 'required|integer|min:1',
            'active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $method = ShippingSetting::findOrFail($id);
        $method->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Shipping method updated successfully',
            'data' => $method
        ]);
    }

    /**
     * Create new shipping method
     */
    public function createMethod(Request $request)
    {
        $request->validate([
            'method_code' => 'required|string|unique:shipping_settings,method_code',
            'method_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'delivery_time_min' => 'required|integer|min:1',
            'delivery_time_max' => 'required|integer|min:1',
            'active' => 'boolean',
            'sort_order' => 'integer'
        ]);

        $method = ShippingSetting::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Shipping method created successfully',
            'data' => $method
        ], 201);
    }

    /**
     * Delete shipping method
     */
    public function deleteMethod($id)
    {
        $method = ShippingSetting::findOrFail($id);
        $method->delete();

        return response()->json([
            'success' => true,
            'message' => 'Shipping method deleted successfully'
        ]);
    }

    /**
     * Update general settings
     */
    public function updateGeneralSettings(Request $request)
    {
        $request->validate([
            'free_shipping_threshold' => 'required|numeric|min:0'
        ]);

        GeneralSetting::set('free_shipping_threshold', $request->free_shipping_threshold);

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully'
        ]);
    }

    /**
     * Toggle shipping method active status
     */
    public function toggleActive($id)
    {
        $method = ShippingSetting::findOrFail($id);
        $method->active = !$method->active;
        $method->save();

        return response()->json([
            'success' => true,
            'message' => 'Shipping method status updated',
            'data' => $method
        ]);
    }
}
