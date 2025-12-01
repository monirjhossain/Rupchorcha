<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display slider listing
     */
    public function index(Request $request)
    {
        $query = Slider::query();

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by locale
        if ($request->has('locale') && $request->locale) {
            $query->where('locale', $request->locale);
        }

        $sliders = $query->orderBy('sort_order', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.sliders.index', compact('sliders'));
    }

    /**
     * Show create slider form
     */
    public function create()
    {
        return view('admin.sliders.create');
    }

    /**
     * Store new slider
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'slider_path' => 'nullable|url',
            'locale' => 'nullable|string|max:10',
            'expired_at' => 'nullable|date',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['title']) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('sliders', $imageName, 'public');
            $validated['path'] = $imagePath;
        }

        $validated['channel_id'] = 1; // Default channel
        $validated['locale'] = $validated['locale'] ?? 'en';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        try {
            Slider::create($validated);

            return redirect()->route('admin.sliders.index')
                ->with('success', 'Slider created successfully!');
        } catch (\Exception $e) {
            // Delete uploaded image if slider creation fails
            if (!empty($validated['path'])) {
                Storage::disk('public')->delete($validated['path']);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create slider: ' . $e->getMessage());
        }
    }

    /**
     * Show edit slider form
     */
    public function edit($id)
    {
        $slider = Slider::findOrFail($id);
        return view('admin.sliders.edit', compact('slider'));
    }

    /**
     * Update slider
     */
    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'slider_path' => 'nullable|url',
            'locale' => 'nullable|string|max:10',
            'expired_at' => 'nullable|date',
            'sort_order' => 'nullable|integer',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($slider->path && !str_starts_with($slider->path, 'http')) {
                Storage::disk('public')->delete($slider->path);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['title']) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('sliders', $imageName, 'public');
            $validated['path'] = $imagePath;
        }

        $validated['locale'] = $validated['locale'] ?? 'en';
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        try {
            $slider->update($validated);

            return redirect()->back()
                ->with('success', 'Slider updated successfully!');
        } catch (\Exception $e) {
            // Delete uploaded image if update fails
            if (!empty($validated['path']) && $validated['path'] !== $slider->path) {
                Storage::disk('public')->delete($validated['path']);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update slider: ' . $e->getMessage());
        }
    }

    /**
     * Delete slider
     */
    public function destroy($id)
    {
        try {
            $slider = Slider::findOrFail($id);

            // Delete slider image
            if ($slider->path && !str_starts_with($slider->path, 'http')) {
                Storage::disk('public')->delete($slider->path);
            }

            $slider->delete();

            return redirect()->route('admin.sliders.index')
                ->with('success', 'Slider deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete slider: ' . $e->getMessage());
        }
    }

    /**
     * Mass delete sliders
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:sliders,id'
        ]);

        try {
            $sliders = Slider::whereIn('id', $request->ids)->get();

            foreach ($sliders as $slider) {
                // Delete slider image
                if ($slider->path && !str_starts_with($slider->path, 'http')) {
                    Storage::disk('public')->delete($slider->path);
                }

                $slider->delete();
            }

            return redirect()->route('admin.sliders.index')
                ->with('success', count($request->ids) . ' slider(s) deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete sliders: ' . $e->getMessage());
        }
    }

    /**
     * Delete slider image via AJAX
     */
    public function deleteImage($id)
    {
        try {
            $slider = Slider::findOrFail($id);

            if ($slider->path && !str_starts_with($slider->path, 'http')) {
                Storage::disk('public')->delete($slider->path);
                $slider->update(['path' => null]);

                return response()->json([
                    'success' => true,
                    'message' => 'Image deleted successfully!'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'No image to delete'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete image: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reorder sliders
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:sliders,id',
            'positions.*.sort_order' => 'required|integer'
        ]);

        try {
            foreach ($request->positions as $item) {
                Slider::where('id', $item['id'])
                    ->update(['sort_order' => $item['sort_order']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sliders reordered successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder sliders: ' . $e->getMessage()
            ], 500);
        }
    }
}
