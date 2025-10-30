<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            return response()->json(['data' => Facility::orderBy('name')->get()]);
        }

        return Inertia::render('admin/facility/index', [
            'facilities' => Facility::latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:255', 'unique:facilities,slug'],
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
        $facility = Facility::create($data);
        return response()->json($facility, 201);
    }

    public function update(Request $request, Facility $facility)
    {
        $data = $request->validate([
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('facilities', 'slug')->ignore($facility->id)],
            'name' => ['sometimes', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
        ]);
        $facility->update($data);
        return response()->json($facility);
    }

    public function destroy(Facility $facility)
    {
        // detach from pivot if relation exists
        try {
            if (method_exists($facility, 'destinations')) {
                $facility->destinations()->detach();
            }
        } catch (\Throwable $e) {
            // ignore detach errors
        }
        $facility->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
