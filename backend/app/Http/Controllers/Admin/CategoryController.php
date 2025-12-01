<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    /**
     * Display category listing with tree structure
     */
    public function index(Request $request)
    {
        $query = Category::with(['parent', 'children'])
            ->withCount('products');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        // Filter by parent category
        if ($request->has('parent_id')) {
            if ($request->parent_id === '0') {
                $query->whereNull('parent_id');
            } elseif ($request->parent_id) {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Get categories with pagination
        $categories = $query->orderBy('position', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(20);

        // Get root categories for filter dropdown
        $rootCategories = Category::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.categories.index', compact('categories', 'rootCategories'));
    }

    /**
     * Show create category form
     */
    public function create()
    {
        // Get all active categories for parent selection (exclude current to prevent circular reference)
        $categories = Category::active()
            ->orderBy('name')
            ->get();

        return view('admin.categories.create', compact('categories'));
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
            'position' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure unique slug
            $count = 1;
            $originalSlug = $validated['slug'];
            while (Category::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['name']) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('categories', $imageName, 'public');
            $validated['image'] = $imagePath;
        }

        try {
            Category::create($validated);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Category created successfully!');
        } catch (\Exception $e) {
            // Delete uploaded image if category creation fails
            if (!empty($validated['image'])) {
                Storage::disk('public')->delete($validated['image']);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Show edit category form
     */
    public function edit($id)
    {
        $category = Category::with(['parent', 'children'])->findOrFail($id);

        // Get all categories except current and its descendants for parent selection
        $categories = Category::where('id', '!=', $id)
            ->orderBy('name')
            ->get()
            ->filter(function($cat) use ($category) {
                // Exclude descendants to prevent circular reference
                return !in_array($cat->id, $category->children->pluck('id')->toArray());
            });

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'status' => 'required|boolean',
            'position' => 'nullable|integer',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Prevent setting itself as parent
        if (!empty($validated['parent_id']) && $validated['parent_id'] == $id) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A category cannot be its own parent!');
        }

        // Prevent circular reference (parent cannot be a descendant)
        if (!empty($validated['parent_id'])) {
            $parentCategory = Category::find($validated['parent_id']);
            $parentIds = collect($parentCategory->parents)->pluck('id')->toArray();
            
            if (in_array($id, $parentIds)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Cannot create circular category reference!');
            }
        }

        // Auto-generate slug if not provided
        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            
            // Ensure unique slug
            $count = 1;
            $originalSlug = $validated['slug'];
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $image = $request->file('image');
            $imageName = time() . '_' . Str::slug($validated['name']) . '.' . $image->getClientOriginalExtension();
            $imagePath = $image->storeAs('categories', $imageName, 'public');
            $validated['image'] = $imagePath;
        }

        try {
            $category->update($validated);

            return redirect()->back()
                ->with('success', 'Category updated successfully!');
        } catch (\Exception $e) {
            // Delete uploaded image if update fails
            if (!empty($validated['image']) && $validated['image'] !== $category->image) {
                Storage::disk('public')->delete($validated['image']);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        try {
            $category = Category::with(['children', 'products'])->findOrFail($id);

            // Check if category has children
            if ($category->children->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete category with child categories! Please delete or reassign child categories first.');
            }

            // Check if category has products
            if ($category->products->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete category with ' . $category->products->count() . ' product(s)! Please reassign products first.');
            }

            // Delete category image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }

            $category->delete();

            return redirect()->route('admin.categories.index')
                ->with('success', 'Category deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Mass delete categories
     */
    public function massDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:categories,id'
        ]);

        try {
            $categories = Category::with(['children', 'products'])
                ->whereIn('id', $request->ids)
                ->get();

            $deleted = 0;
            $errors = [];

            foreach ($categories as $category) {
                // Check if category has children
                if ($category->children->count() > 0) {
                    $errors[] = "Category '{$category->name}' has child categories";
                    continue;
                }

                // Check if category has products
                if ($category->products->count() > 0) {
                    $errors[] = "Category '{$category->name}' has {$category->products->count()} product(s)";
                    continue;
                }

                // Delete category image
                if ($category->image) {
                    Storage::disk('public')->delete($category->image);
                }

                $category->delete();
                $deleted++;
            }

            $message = $deleted > 0 ? "{$deleted} category(ies) deleted successfully!" : '';
            if (!empty($errors)) {
                $message .= ' Errors: ' . implode(', ', $errors);
            }

            return redirect()->route('admin.categories.index')
                ->with($deleted > 0 ? 'success' : 'error', $message);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to delete categories: ' . $e->getMessage());
        }
    }

    /**
     * Delete category image via AJAX
     */
    public function deleteImage($id)
    {
        try {
            $category = Category::findOrFail($id);

            if ($category->image) {
                Storage::disk('public')->delete($category->image);
                $category->update(['image' => null]);

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
     * Reorder categories (for drag-and-drop)
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'positions' => 'required|array',
            'positions.*.id' => 'required|exists:categories,id',
            'positions.*.position' => 'required|integer'
        ]);

        try {
            foreach ($request->positions as $item) {
                Category::where('id', $item['id'])
                    ->update(['position' => $item['position']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Categories reordered successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reorder categories: ' . $e->getMessage()
            ], 500);
        }
    }
}
