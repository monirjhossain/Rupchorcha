<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * Display a listing of tags
     */
    public function index(Request $request)
    {
        $query = Tag::query();
        
        // Search by name or slug
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }
        
        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        $tags = $query->withCount('products')
                      ->orderBy('name')
                      ->paginate(15);
        
        return view('admin.tags.index', compact('tags'));
    }

    /**
     * Show the form for creating a new tag
     */
    public function create()
    {
        return view('admin.tags.create');
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug',
            'color' => 'required|string|max:7',
            'status' => 'required|boolean'
        ]);
        
        $data = $request->all();
        
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        Tag::create($data);
        
        return redirect()->route('admin.tags.index')
                        ->with('success', 'Tag created successfully');
    }

    /**
     * Show the form for editing tag
     */
    public function edit($id)
    {
        $tag = Tag::findOrFail($id);
        return view('admin.tags.edit', compact('tag'));
    }

    /**
     * Update the tag
     */
    public function update(Request $request, $id)
    {
        $tag = Tag::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:tags,slug,' . $id,
            'color' => 'required|string|max:7',
            'status' => 'required|boolean'
        ]);
        
        $data = $request->all();
        
        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $tag->update($data);
        
        return redirect()->route('admin.tags.index')
                        ->with('success', 'Tag updated successfully');
    }

    /**
     * Remove the tag
     */
    public function destroy($id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();
        
        return redirect()->route('admin.tags.index')
                        ->with('success', 'Tag deleted successfully');
    }

    /**
     * Mass destroy tags
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:tags,id'
        ]);
        
        Tag::whereIn('id', $request->ids)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Tags deleted successfully'
        ]);
    }
}
