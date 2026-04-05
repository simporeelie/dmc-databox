<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Categories/Index', [
            'categories' => Category::with('subcategories')->withCount('documents')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:categories',
            'icon' => 'nullable|string|max:50',
        ]);

        Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'icon' => $request->icon,
        ]);

        return back()->with('success', 'Catégorie créée.');
    }

    public function storeSubcategory(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:191',
            'category_id' => 'required|exists:categories,id',
        ]);

        Subcategory::create([
            'name'        => $request->name,
            'slug'        => Str::slug($request->name . '-' . $request->category_id),
            'category_id' => $request->category_id,
        ]);

        return back()->with('success', 'Sous-catégorie créée.');
    }

    public function destroySubcategory(Subcategory $subcategory)
    {
        $subcategory->delete();
        return back()->with('success', 'Sous-catégorie supprimée.');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return back()->with('success', 'Catégorie supprimée.');
    }
}
