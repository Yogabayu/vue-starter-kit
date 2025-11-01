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
use App\Models\DestinationOpenHour;
use App\Models\District;
use App\Models\Village;
use App\Models\Tag;

class DestinationController extends Controller
{
    public function index(): InertiaResponse
    {

        $destinations = Destination::with(['detail', 'detail.village', 'detail.village.district', 'coverImage', 'images', 'categories', 'openHours'])
            ->latest()
            ->get();

        return Inertia::render('admin/destination/index', [
            'destinations' => $destinations
        ]);
    }

    public function show($id)
    {
        $destination = Destination::with(['detail', 'detail.village', 'detail.village.district', 'categories', 'facilities', 'tags', 'openHours', 'images'])->findOrFail($id);
        return response()->json($destination);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:destinations,slug'],
            'name' => ['required', 'string', 'max:255'],

            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],

            'facilities' => ['array'],
            'facilities.*' => ['integer', 'exists:facilities,id'],

            // tags can be a mix of id (int) and name (string)
            'tags' => ['array'],

            'detail' => ['nullable'],
            'open_hours' => ['nullable', 'string'],

            'images' => ['array'],
            'images.*.file' => ['nullable', 'file', 'image'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.is_cover' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($request, $validated) {

            $slug = $validated['slug'] ?? Str::slug($validated['name'] . '-' . time());


            $destination = Destination::create([
                'user_id' => $validated['user_id'],
                'slug' => $slug,
                'name' => $validated['name'],
            ]);
            if ($request->filled('detail')) {
                $detail = json_decode($request->input('detail'), true);
                if (is_array($detail)) {

                    if (isset($detail['maps_link']) && empty($detail['map_url'])) {
                        $detail['map_url'] = $detail['maps_link'];
                        unset($detail['maps_link']);
                    }

                    foreach (['ticket_price'] as $numField) {
                        if (array_key_exists($numField, $detail)) {
                            $detail[$numField] = $detail[$numField] === '' ? null : $detail[$numField];
                        }
                    }

                    if (empty($detail['district_id']) || !is_numeric($detail['district_id'])) {
                        $districtCode = $detail['district'] ?? ($detail['district_id'] ?? null);
                        if ($districtCode) {
                            $district = District::where('code', $districtCode)->first();
                            if ($district) {
                                $detail['district_id'] = $district->id;
                            } else {
                                unset($detail['district_id']);
                            }
                        }
                    }

                    if (empty($detail['village_id']) || !is_numeric($detail['village_id'])) {
                        $villageCode = $detail['village'] ?? ($detail['village_id'] ?? null);
                        if ($villageCode) {
                            $village = Village::where('code', $villageCode)->first();
                            if ($village) {
                                $detail['village_id'] = $village->id;
                            } else {
                                unset($detail['village_id']);
                            }
                        }
                    }
                    $payload = collect($detail)->only([
                        'description',
                        'address',
                        'village_id',
                        'district_id',
                        'map_url',
                        'ticket_price',
                        'currency',
                        'phone',
                        'status',
                        'published_at'
                    ])->toArray();
                    $destination->detail()->create($payload);
                }
            }

            if (!empty($validated['categories'])) {
                $destination->categories()->sync($validated['categories']);
            }

            if (!empty($validated['facilities'])) {
                $destination->facilities()->sync($validated['facilities']);
            }

            // Handle tags (int ids or string names) + backward compat with new_tags
            $finalTagIds = [];
            $incomingTags = $request->input('tags', []);
            if (is_array($incomingTags)) {
                foreach ($incomingTags as $val) {
                    if (is_numeric($val)) {
                        $tag = Tag::find((int)$val);
                        if ($tag) $finalTagIds[] = $tag->id;
                    } elseif (is_string($val)) {
                        $name = trim($val);
                        if ($name === '') continue;
                        $slug = Str::slug($name);
                        $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                        $finalTagIds[] = $tag->id;
                    }
                }
            }
            $incomingNewTags = $request->input('new_tags', []);
            if (is_array($incomingNewTags)) {
                foreach ($incomingNewTags as $tagName) {
                    if (!is_string($tagName)) continue;
                    $name = trim($tagName);
                    if ($name === '') continue;
                    $slug = Str::slug($name);
                    $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                    $finalTagIds[] = $tag->id;
                }
            }
            $finalTagIds = array_values(array_unique($finalTagIds));
            if (!empty($finalTagIds)) $destination->tags()->sync($finalTagIds);


            if ($request->filled('open_hours')) {
                $oh = json_decode($request->string('open_hours'), true);
                if (is_array($oh)) {
                    foreach ($oh as $row) {
                        if (!isset($row['day_of_week'])) continue;
                        $dow = (int) $row['day_of_week'];
                        if ($dow < 1 || $dow > 7) continue;
                        $destination->openHours()->updateOrCreate(
                            ['day_of_week' => $dow],
                            [
                                'open_time' => $row['is_closed'] ? null : ($row['open_time'] ?? null),
                                'close_time' => $row['is_closed'] ? null : ($row['close_time'] ?? null),
                                'is_closed' => (bool) ($row['is_closed'] ?? false),
                                'notes' => $row['notes'] ?? null,
                            ],
                        );
                    }
                }
            }


            if ($request->has('images')) {
                foreach ($request->file('images', []) as $i => $fileGroup) {

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

            'facilities' => ['sometimes', 'array'],
            'facilities.*' => ['integer', 'exists:facilities,id'],

            // tags can be a mix of id (int) and name (string)
            'tags' => ['sometimes', 'array'],

            'detail' => ['sometimes'],

            'open_hours' => ['sometimes', 'string'],

            'images' => ['sometimes', 'array'],
            'images.*.id' => ['nullable', 'integer', 'exists:destination_images,id'],
            'images.*.file' => ['nullable', 'file', 'image'],
            'images.*.caption' => ['nullable', 'string', 'max:255'],
            'images.*.is_cover' => ['nullable', 'boolean'],
        ]);

        return DB::transaction(function () use ($validated, $request, $destination) {


            $destination->update([
                'slug' => $validated['slug'] ?? $destination->slug,
                'name' => $validated['name'] ?? $destination->name,
            ]);


            if ($request->filled('detail')) {
                $detail = json_decode($request->input('detail'), true);
                if (is_array($detail)) {
                    if (isset($detail['maps_link']) && empty($detail['map_url'])) {
                        $detail['map_url'] = $detail['maps_link'];
                        unset($detail['maps_link']);
                    }
                    foreach (['ticket_price'] as $numField) {
                        if (array_key_exists($numField, $detail)) {
                            $detail[$numField] = $detail[$numField] === '' ? null : $detail[$numField];
                        }
                    }
                    // Map district code -> id when district_id missing or non-numeric
                    if (empty($detail['district_id']) || !is_numeric($detail['district_id'])) {
                        $districtCode = $detail['district'] ?? ($detail['district_id'] ?? null);
                        if ($districtCode) {
                            $district = District::where('code', $districtCode)->first();
                            if ($district) {
                                $detail['district_id'] = $district->id;
                            } else {
                                unset($detail['district_id']);
                            }
                        }
                    }
                    // Map village code -> id when village_id missing or non-numeric
                    if (empty($detail['village_id']) || !is_numeric($detail['village_id'])) {
                        $villageCode = $detail['village'] ?? ($detail['village_id'] ?? null);
                        if ($villageCode) {
                            $village = Village::where('code', $villageCode)->first();
                            if ($village) {
                                $detail['village_id'] = $village->id;
                            } else {
                                unset($detail['village_id']);
                            }
                        }
                    }
                    $payload = collect($detail)->only([
                        'description',
                        'address',
                        'district_id',
                        'village_id',
                        'map_url',
                        'ticket_price',
                        'currency',
                        'phone',
                        'status',
                        'published_at'
                    ])->toArray();
                    $destination->detail()->updateOrCreate(
                        ['destination_id' => $destination->id],
                        $payload
                    );
                }
            }


            if (array_key_exists('categories', $validated)) {
                $destination->categories()->sync($validated['categories'] ?? []);
            }

            if (array_key_exists('facilities', $validated)) {
                $destination->facilities()->sync($validated['facilities'] ?? []);
            }

            // Handle tags + new_tags for update
            if ($request->has('tags') || $request->has('new_tags')) {
                $finalTagIds = [];
                $incomingTags = $request->input('tags', []);
                if (is_array($incomingTags)) {
                    foreach ($incomingTags as $val) {
                        if (is_numeric($val)) {
                            $tag = Tag::find((int)$val);
                            if ($tag) $finalTagIds[] = $tag->id;
                        } elseif (is_string($val)) {
                            $name = trim($val);
                            if ($name === '') continue;
                            $slug = Str::slug($name);
                            $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                            $finalTagIds[] = $tag->id;
                        }
                    }
                }
                $incomingNewTags = $request->input('new_tags', []);
                if (is_array($incomingNewTags)) {
                    foreach ($incomingNewTags as $tagName) {
                        if (!is_string($tagName)) continue;
                        $name = trim($tagName);
                        if ($name === '') continue;
                        $slug = Str::slug($name);
                        $tag = Tag::firstOrCreate(['slug' => $slug], ['name' => $name]);
                        $finalTagIds[] = $tag->id;
                    }
                }
                $finalTagIds = array_values(array_unique($finalTagIds));
                $destination->tags()->sync($finalTagIds);
            }


            if ($request->filled('open_hours')) {
                $oh = json_decode($request->string('open_hours'), true);
                if (is_array($oh)) {
                    foreach ($oh as $row) {
                        if (!isset($row['day_of_week'])) continue;
                        $dow = (int) $row['day_of_week'];
                        if ($dow < 1 || $dow > 7) continue;
                        $destination->openHours()->updateOrCreate(
                            ['day_of_week' => $dow],
                            [
                                'open_time' => $row['is_closed'] ? null : ($row['open_time'] ?? null),
                                'close_time' => $row['is_closed'] ? null : ($row['close_time'] ?? null),
                                'is_closed' => (bool) ($row['is_closed'] ?? false),
                                'notes' => $row['notes'] ?? null,
                            ],
                        );
                    }
                }
            }


            if ($request->has('images')) {
                foreach ($request->file('images', []) as $i => $fileGroup) {

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
