<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\DetailDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Support\Str;

class DestinationController extends Controller
{
    public function index(): InertiaResponse
    {

        $destinations = Destination::with(['detail', 'coverImage', 'categories'])
            ->latest()
            ->get();

        return Inertia::render('admin/destination/index', [
            'destinations' => $destinations
        ]);
    }

    public function show($id)
    {
        $destination = Destination::with(['detail', 'images', 'categories'])->findOrFail($id);
        return response()->json($destination);
    }


    public function store(Request $request)
    {
        // 1️⃣ Validasi dasar
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:destinations,slug'],
            'name' => ['required', 'string', 'max:255'],

            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],

            'detail' => ['nullable'], // nanti di-decode manual

            // Validasi file upload
            'images' => ['array'],
            'images.*.file' => ['nullable', 'file', 'image'], // max 2MB
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.is_cover' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // 2️⃣ Generate slug otomatis
            $slug = $validated['slug'] ?? Str::slug($validated['name']);

            // 3️⃣ Buat destinasi utama
            $destination = Destination::create([
                'user_id' => $validated['user_id'],
                'slug' => $slug,
                'name' => $validated['name'],
            ]);
            if ($request->filled('detail')) {
                $detail = json_decode($request->input('detail'), true);
                if (is_array($detail)) {
                    // Normalisasi data: ubah string kosong jadi null
                    foreach (['latitude', 'longitude', 'ticket_price'] as $numField) {
                        if (isset($detail[$numField]) && $detail[$numField] === '') {
                            $detail[$numField] = null;
                        }
                    }

                    $destination->detail()->create($detail);
                }
            }


            // 5️⃣ Simpan kategori (pivot)
            if (!empty($validated['categories'])) {
                $destination->categories()->sync($validated['categories']);
            }

            // 6️⃣ Simpan gambar
            if ($request->has('images')) {
                foreach ($request->file('images', []) as $i => $fileGroup) {
                    // kalau images dikirim pakai struktur: images[0][file]
                    $file = $request->file("images.$i.file");
                    $caption = $request->input("images.$i.caption");
                    $isCover = (bool) $request->input("images.$i.is_cover");

                    $url = null;
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $url = $file->store('destinations', 'public');
                    }

                    $destination->images()->create([
                        'image_url' => $url,
                        'caption' => $caption,
                        'is_cover' => $isCover,
                    ]);
                }
            }

            return response()->json(
                $destination->load(['detail', 'categories', 'images']),
                201
            );
        });
    }

    public function update(Request $request, $id)
    {
        $destination = Destination::with('detail', 'categories', 'images')->findOrFail($id);

        $validated = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('destinations', 'slug')->ignore($destination->id)],
            'name' => ['sometimes', 'string', 'max:255'],

            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],

            'detail' => ['sometimes'], // JSON string dari frontend

            'images' => ['sometimes', 'array'],
            'images.*.id' => ['nullable', 'integer', 'exists:destination_images,id'],
            'images.*.file' => ['nullable', 'file', 'image'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.is_cover' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated, $request, $destination) {

            // 1️⃣ Update field dasar
            $destination->update([
                'slug' => $validated['slug'] ?? $destination->slug,
                'name' => $validated['name'] ?? $destination->name,
            ]);

            // 2️⃣ Update detail
            if ($request->filled('detail')) {
                $detail = json_decode($request->input('detail'), true);

                if (is_array($detail)) {
                    // ubah string kosong jadi null
                    foreach (['latitude', 'longitude', 'ticket_price'] as $numField) {
                        if (isset($detail[$numField]) && $detail[$numField] === '') {
                            $detail[$numField] = null;
                        }
                    }

                    $destination->detail()->updateOrCreate(
                        ['destination_id' => $destination->id],
                        $detail
                    );
                }
            }

            // 3️⃣ Update kategori (pivot)
            if (array_key_exists('categories', $validated)) {
                $destination->categories()->sync($validated['categories'] ?? []);
            }

            // 4️⃣ Update / Simpan gambar
            if ($request->has('images')) {
                foreach ($request->file('images', []) as $i => $fileGroup) {
                    // kalau images dikirim pakai struktur: images[0][file]
                    $file = $request->file("images.$i.file");
                    $caption = $request->input("images.$i.caption");
                    $isCover = (bool) $request->input("images.$i.is_cover");

                    $url = null;
                    if ($file instanceof \Illuminate\Http\UploadedFile) {
                        $url = $file->store('destinations', 'public');
                    }

                    $destination->images()->create([
                        'image_url' => $url,
                        'caption' => $caption,
                        'is_cover' => $isCover,
                    ]);
                }
            }

            return response()->json(
                $destination->load(['detail', 'categories', 'images']),
                200
            );
        });
    }

    public function destroy($id)
    {
        $destination = Destination::with('images', 'detail')->findOrFail($id);

        return DB::transaction(function () use ($destination) {
            foreach ($destination->images as $image) {
                if ($image->image_url && Storage::disk('public')->exists($image->image_url)) {
                    Storage::disk('public')->delete($image->image_url);
                }
            }

            $destination->categories()->detach();
            $destination->images()->delete();
            $destination->detail()->delete();

            $destination->delete();

            return response()->json(['message' => 'Deleted']);
        });
    }
}
