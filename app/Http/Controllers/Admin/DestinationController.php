<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Destination;
use App\Models\DetailDestination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

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

    public function show(Destination $destination)
    {
        $destination->load(['detail', 'images', 'categories']);
        return response()->json($destination);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'slug' => ['required', 'string', 'max:255', 'unique:destinations,slug'],
            'name' => ['required', 'string', 'max:255'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'detail' => ['array'],
            'detail.description' => ['nullable', 'string'],
            'detail.address' => ['nullable', 'string', 'max:255'],
            'detail.village' => ['nullable', 'string', 'max:255'],
            'detail.district' => ['nullable', 'string', 'max:255'],
            'detail.latitude' => ['nullable', 'numeric'],
            'detail.longitude' => ['nullable', 'numeric'],
            'detail.ticket_price' => ['nullable', 'numeric'],
            'detail.open_hours' => ['nullable', 'string', 'max:255'],
            'detail.close_hours' => ['nullable', 'string', 'max:255'],
            'detail.cover_image' => ['nullable', 'string', 'max:255'],
            'detail.phone' => ['nullable', 'string', 'max:255'],
            'detail.status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);

        return DB::transaction(function () use ($data) {
            $destination = Destination::create([
                'user_id' => $data['user_id'],
                'slug' => $data['slug'],
                'name' => $data['name'],
            ]);

            if (!empty($data['detail'])) {
                $destination->detail()->create($data['detail']);
            }

            if (!empty($data['categories'])) {
                $destination->categories()->sync($data['categories']);
            }

            return response()->json($destination->load(['detail', 'categories']), 201);
        });
    }

    public function update(Request $request, Destination $destination)
    {

        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('destinations', 'slug')->ignore($destination->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'detail' => ['sometimes', 'array'],
            'detail.description' => ['nullable', 'string'],
            'detail.address' => ['nullable', 'string', 'max:255'],
            'detail.village' => ['nullable', 'string', 'max:255'],
            'detail.district' => ['nullable', 'string', 'max:255'],
            'detail.latitude' => ['nullable', 'numeric'],
            'detail.longitude' => ['nullable', 'numeric'],
            'detail.ticket_price' => ['nullable', 'numeric'],
            'detail.open_hours' => ['nullable', 'string', 'max:255'],
            'detail.close_hours' => ['nullable', 'string', 'max:255'],
            'detail.cover_image' => ['nullable', 'string', 'max:255'],
            'detail.phone' => ['nullable', 'string', 'max:255'],
            'detail.status' => ['nullable', Rule::in(['draft', 'published'])],
        ]);

        return DB::transaction(function () use ($data, $destination) {
            $destination->update($data);

            if (array_key_exists('detail', $data)) {
                $destination->detail()->updateOrCreate(
                    ['destination_id' => $destination->id],
                    $data['detail']
                );
            }

            if (array_key_exists('categories', $data)) {
                $destination->categories()->sync($data['categories'] ?? []);
            }

            return response()->json($destination->load(['detail', 'categories']));
        });
    }

    public function destroy(Destination $destination)
    {

        return DB::transaction(function () use ($destination) {
            $destination->categories()->detach();
            $destination->images()->delete();
            $destination->detail()->delete();
            $destination->delete();
            return response()->json(['message' => 'Deleted']);
        });
    }
}
