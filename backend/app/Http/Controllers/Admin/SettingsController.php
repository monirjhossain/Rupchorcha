<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function general()
    {
        return view('admin.settings.general');
    }

    public function update(Request $request)
    {
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $request->validate([
                'logo' => 'image|mimes:png,jpg,jpeg|max:2048'
            ]);

            $logo = $request->file('logo');
            $logoPath = public_path('logo.png');
            
            // Delete old logo if exists
            if (File::exists($logoPath)) {
                File::delete($logoPath);
            }
            
            // Save new logo
            $logo->move(public_path(), 'logo.png');
        }

        return redirect()->route('admin.settings.general')
            ->with('success', 'Settings updated successfully!');
    }
}
