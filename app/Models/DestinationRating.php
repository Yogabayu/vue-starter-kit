<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_id', 'ratings_count', 'ratings_avg',
    ];

    protected $casts = [
        'ratings_count' => 'integer',
        'ratings_avg' => 'decimal:2',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}

