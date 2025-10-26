<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DestinationOpenHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination_id', 'day_of_week', 'open_time', 'close_time', 'is_closed', 'notes',
    ];

    protected $casts = [
        'is_closed' => 'boolean',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}

