<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::query()->latest()->get();
        return response()->json($banners);
    }

    public function show(Banner $banner)
    {
        return response()->json($banner);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['required', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $banner = Banner::create($data);
        return response()->json($banner, 201);
    }

    public function update(Request $request, Banner $banner)
    {

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'image_url' => ['sometimes', 'string', 'max:255'],
            'link_url' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $banner->update($data);
        return response()->json($banner);
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
