<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\DestinationImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DestinationImageController extends Controller
{
    public function store(Request $request, Destination $destination)
    {
        $data = $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120'], // 5MB
            'caption' => ['nullable', 'string', 'max:255'],
            'is_cover' => ['boolean'],
        ]);

        $path = $request->file('image')->store('destinations', 'public');

        if (!empty($data['is_cover']) && $data['is_cover']) {
            DestinationImage::where('destination_id', $destination->id)->update(['is_cover' => false]);
        }

        $image = $destination->images()->create([
            'image_url' => Storage::disk('public')->url($path),
            'caption' => $data['caption'] ?? null,
            'is_cover' => (bool)($data['is_cover'] ?? false),
        ]);

        return response()->json($image, 201);
    }

    public function destroy(Destination $destination, DestinationImage $image)
    {
        // Optional ownership/authorization checks could go here
        if ($image->destination_id !== $destination->id) {
            abort(404);
        }

        // Try to delete physical file if stored under public disk URL
        $publicUrl = $image->image_url;
        $relativePath = $publicUrl ? Str::after($publicUrl, '/storage/') : null;
        if ($relativePath) {
            Storage::disk('public')->delete($relativePath);
        }

        $image->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
