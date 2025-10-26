<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::query()->latest()->paginate(12);
        return response()->json($events);
    }

    public function show(Event $event)
    {
        return response()->json($event);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'slug' => ['required', 'string', 'max:255', 'unique:events,slug'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'village' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'banner_image' => ['nullable', 'string', 'max:255'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'organizer_contact' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'string', 'max:255'],
            'is_free' => ['boolean'],
            'price_min' => ['nullable', 'numeric'],
            'price_max' => ['nullable', 'numeric'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:event_categories,id'],
        ]);

        if (empty($data['village_id']) && $request->filled('village')) {
            $v = \App\Models\Village::where('code', $request->string('village'))->first();
            if ($v) $data['village_id'] = $v->id;
        }

        $event = Event::create($data);
        if (!empty($data['categories'])) {
            $event->categories()->sync($data['categories']);
        }
        return response()->json($event, 201);
    }

    public function update(Request $request, Event $event)
    {

        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($event->id)],
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'village_id' => ['nullable', 'exists:villages,id'],
            'village' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'banner_image' => ['nullable', 'string', 'max:255'],
            'organizer_name' => ['nullable', 'string', 'max:255'],
            'organizer_contact' => ['nullable', 'string', 'max:255'],
            'registration_url' => ['nullable', 'string', 'max:255'],
            'is_free' => ['boolean'],
            'price_min' => ['nullable', 'numeric'],
            'price_max' => ['nullable', 'numeric'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:event_categories,id'],
        ]);

        if (empty($data['village_id']) && $request->filled('village')) {
            $v = \App\Models\Village::where('code', $request->string('village'))->first();
            if ($v) $data['village_id'] = $v->id;
        }

        $event->update($data);
        if (array_key_exists('categories', $data)) {
            $event->categories()->sync($data['categories'] ?? []);
        }
        return response()->json($event);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
