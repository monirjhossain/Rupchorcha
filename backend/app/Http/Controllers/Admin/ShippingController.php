<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingSetting;
use App\Models\GeneralSetting;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    /**
     * Display shipping settings page
     */
    public function index()
    {
        $methods = ShippingSetting::orderBy('sort_order')->get();
        $freeShippingThreshold = GeneralSetting::get('free_shipping_threshold', 3000);
        $freeShippingEnabled = GeneralSetting::get('free_shipping_enabled', 1);

        return view('admin.settings.shipping.index', compact('methods', 'freeShippingThreshold', 'freeShippingEnabled'));
    }

    /**
     * Update shipping method
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'method_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'delivery_time_min' => 'required|integer|min:1',
            'delivery_time_max' => 'required|integer|min:1',
            'active' => 'boolean',
        ]);

        $method = ShippingSetting::findOrFail($id);
        $method->update($request->all());

        session()->flash('success', 'Shipping method updated successfully');
        return redirect()->back();
    }

    /**
     * Update general shipping settings
     */
    public function updateGeneral(Request $request)
    {
        $request->validate([
            'free_shipping_threshold' => 'required|numeric|min:0',
            'free_shipping_enabled' => 'nullable|boolean',
        ]);

        GeneralSetting::set('free_shipping_threshold', $request->free_shipping_threshold);
        GeneralSetting::set('free_shipping_enabled', $request->has('free_shipping_enabled') ? 1 : 0);

        session()->flash('success', 'General settings updated successfully');
        return redirect()->back();
    }

    /**
     * Toggle shipping method status
     */
    public function toggleStatus($id)
    {
        $method = ShippingSetting::findOrFail($id);
        $method->active = !$method->active;
        $method->save();

        session()->flash('success', 'Shipping method status updated');
        return redirect()->back();
    }
}
