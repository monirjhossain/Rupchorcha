<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomAttribute;
use App\Models\CustomAttributeOption;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of attributes
     */
    public function index(Request $request)
    {
        $query = CustomAttribute::query();
        
        // Search by name or slug
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $attributes = $query->withCount('options')
                            ->ordered()
                            ->paginate(15);
        
        return view('admin.attributes.index', compact('attributes'));
    }

    /**
     * Show the form for creating a new attribute
     */
    public function create()
    {
        return view('admin.attributes.create');
    }

    /**
     * Store a newly created attribute
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:custom_attributes,slug',
            'type' => 'required|in:text,select,color,size',
            'is_filterable' => 'required|boolean',
            'is_required' => 'required|boolean',
            'position' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'options' => 'required_if:type,select,color,size|array',
            'options.*.value' => 'required_with:options|string',
            'options.*.label' => 'required_with:options|string',
            'options.*.color_code' => 'nullable|string',
        ]);
        
        $data = $request->only(['name', 'slug', 'type', 'is_filterable', 'is_required', 'position', 'status']);
        
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $attribute = CustomAttribute::create($data);
        
        // Create options if type is select, color, or size
        if (in_array($request->type, ['select', 'color', 'size']) && $request->has('options')) {
            foreach ($request->options as $index => $option) {
                CustomAttributeOption::create([
                    'attribute_id' => $attribute->id,
                    'value' => $option['value'],
                    'label' => $option['label'],
                    'color_code' => $option['color_code'] ?? null,
                    'position' => $index
                ]);
            }
        }
        
        return redirect()->route('admin.attributes.index')
                        ->with('success', 'Attribute created successfully');
    }

    /**
     * Show the form for editing attribute
     */
    public function edit($id)
    {
        $attribute = CustomAttribute::with('options')->findOrFail($id);
        return view('admin.attributes.edit', compact('attribute'));
    }

    /**
     * Update the attribute
     */
    public function update(Request $request, $id)
    {
        $attribute = CustomAttribute::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:custom_attributes,slug,' . $id,
            'type' => 'required|in:text,select,color,size',
            'is_filterable' => 'required|boolean',
            'is_required' => 'required|boolean',
            'position' => 'required|integer|min:0',
            'status' => 'required|boolean',
            'options' => 'required_if:type,select,color,size|array',
            'options.*.value' => 'required_with:options|string',
            'options.*.label' => 'required_with:options|string',
            'options.*.color_code' => 'nullable|string',
        ]);
        
        $data = $request->only(['name', 'slug', 'type', 'is_filterable', 'is_required', 'position', 'status']);
        
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $attribute->update($data);
        
        // Update options if type is select, color, or size
        if (in_array($request->type, ['select', 'color', 'size'])) {
            // Delete existing options
            $attribute->options()->delete();
            
            // Create new options
            if ($request->has('options')) {
                foreach ($request->options as $index => $option) {
                    CustomAttributeOption::create([
                        'attribute_id' => $attribute->id,
                        'value' => $option['value'],
                        'label' => $option['label'],
                        'color_code' => $option['color_code'] ?? null,
                        'position' => $index
                    ]);
                }
            }
        }
        
        return redirect()->route('admin.attributes.index')
                        ->with('success', 'Attribute updated successfully');
    }

    /**
     * Remove the attribute
     */
    public function destroy($id)
    {
        $attribute = CustomAttribute::findOrFail($id);
        $attribute->delete();
        
        return redirect()->route('admin.attributes.index')
                        ->with('success', 'Attribute deleted successfully');
    }

    /**
     * Mass destroy attributes
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:custom_attributes,id'
        ]);
        
        CustomAttribute::whereIn('id', $request->ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Attributes deleted successfully'
        ]);
    }
}
