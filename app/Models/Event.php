<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'title',
        'description',
        'location',
        'village_id',
        'latitude',
        'longitude',
        'start_at',
        'end_at',
        'timezone',
        'banner_image',
        'organizer_name',
        'organizer_contact',
        'registration_url',
        'is_free',
        'price_min',
        'price_max',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_free' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function village(): BelongsTo
    {
        return $this->belongsTo(Village::class);
    }

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(EventCategory::class, 'event_event_category', 'event_id', 'event_category_id');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
