<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function general()
    {
        // Get current settings
        $settings = DB::table('general_settings')->first();
        return view('admin.settings.general', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'admin_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048'
        ]);

        // Get or create settings record
        $settings = DB::table('general_settings')->first();
        
        $data = [
            'site_name' => $request->site_name,
            'contact_email' => $request->contact_email,
            'contact_phone' => $request->contact_phone,
            'updated_at' => now()
        ];

        // Handle admin logo upload
        if ($request->hasFile('admin_logo')) {
            $logo = $request->file('admin_logo');
            $logoName = 'admin-logo-' . time() . '.' . $logo->getClientOriginalExtension();
            
            // Create uploads directory if it doesn't exist
            $uploadPath = public_path('uploads/settings');
            if (!File::exists($uploadPath)) {
                File::makeDirectory($uploadPath, 0755, true);
            }
            
            // Delete old logo if exists
            if ($settings && $settings->admin_logo) {
                $oldLogoPath = public_path($settings->admin_logo);
                if (File::exists($oldLogoPath)) {
                    File::delete($oldLogoPath);
                }
            }
            
            // Save new logo
            $logo->move($uploadPath, $logoName);
            $data['admin_logo'] = 'uploads/settings/' . $logoName;
        }

        // Update or insert settings
        if ($settings) {
            DB::table('general_settings')->where('id', $settings->id)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('general_settings')->insert($data);
        }

        return redirect()->route('admin.settings.general')
            ->with('success', 'Settings updated successfully!');
    }
}
