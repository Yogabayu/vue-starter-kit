<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        // Return JSON for API-style requests (used by Vue fetch calls)
        if ($request->wantsJson()) {
            return response()->json(['data' => Category::orderByDesc('id')->get()]);
        }

        return Inertia::render('admin/category/index', [
            'categories' => Category::latest()->get(),
        ]);
    }

    public function show(Category $category)
    {
        return response()->json($category->load('destinations'));
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:categories,slug'],
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    public function update(Request $request, Category $category)
    {

        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
        $category->update($data);
        return response()->json($category);
    }

    public function destroy(Category $category)
    {

        $category->destinations()->detach();
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
