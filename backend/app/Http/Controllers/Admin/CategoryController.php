<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Webkul\Category\Repositories\CategoryRepository;

class CategoryController extends Controller
{
    protected $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->middleware('auth:admin');
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Display category listing
     */
    public function index()
    {
        $categories = $this->categoryRepository->getVisibleCategoryTree(
            core()->getCurrentChannel()->root_category_id
        );

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show create category form
     */
    public function create()
    {
        $categories = $this->categoryRepository->getCategoryTree();

        return view('admin.categories.create', compact('categories'));
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $request->validate([
            'slug' => 'required|unique:category_translations,slug',
            'name' => 'required',
        ]);

        try {
            $this->categoryRepository->create($request->all());

            return redirect()->route('admin.categories.index')
                ->with('success', 'Category created successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show edit category form
     */
    public function edit($id)
    {
        $category = $this->categoryRepository->findOrFail($id);
        $categories = $this->categoryRepository->getCategoryTreeExcept($id);

        return view('admin.categories.edit', compact('category', 'categories'));
    }

    /**
     * Update category
     */
    public function update(Request $request, $id)
    {
        try {
            $this->categoryRepository->update($request->all(), $id);

            return redirect()->back()->with('success', 'Category updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete category
     */
    public function destroy($id)
    {
        try {
            $category = $this->categoryRepository->findOrFail($id);

            if ($category->children->count() > 0) {
                return redirect()->back()
                    ->with('error', 'Cannot delete category with child categories!');
            }

            $this->categoryRepository->delete($id);

            return redirect()->route('admin.categories.index')
                ->with('success', 'Category deleted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
