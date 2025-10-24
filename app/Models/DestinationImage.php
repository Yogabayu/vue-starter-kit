<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class DestinationImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_id',
        'image_url', // tetap
        'caption',
        'is_cover',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
    ];

    protected $appends = ['full_url'];

    public function getFullUrlAttribute(): ?string
    {
        return $this->image_url
            ? Storage::url($this->image_url)
            : asset('images/default.jpg');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
