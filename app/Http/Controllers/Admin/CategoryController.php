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
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:255'],
            ]);
            $data['slug'] = \Str::slug($data['name']);
            if (Category::where('slug', $data['slug'])->exists()) {
                // throw 'Category with this name already exists';
                return response()->json(['message' => 'Category with this name already exists'], 422);
            }
            $category = Category::create($data);
            return response()->json($category, 201);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }

    public function update(Request $request, $category)
    {
        try {
            $data = $request->validate([
                'name' => ['sometimes', 'string', 'max:255'],
                'icon' => ['nullable', 'string', 'max:255'],
            ]);
            $data['slug'] = \Str::slug($data['name']);
            if (Category::where('slug', $data['slug'])->where('id', '!=', $category->id)->exists()) {
                throw 'Category with this name already exists';
            }
            $category = Category::findOrFail($category);
            $category->update($data);
            return response()->json($category);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Failed to update category'], 500);
        }
    }

    public function destroy($category)
    {
        try {
            $category = Category::findOrFail($category);
            $category->destinations()->detach();
            $category->delete();
            return response()->json(['message' => 'Deleted']);
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Error deleting category'], 500);
        }
    }
}
