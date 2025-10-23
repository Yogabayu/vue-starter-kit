<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailDestination extends Model
{
    use HasFactory;

    protected $table = 'detail_destinations';

    protected $fillable = [
        'destination_id',
        'description',
        'address',
        'village',
        'district',
        'maps_link',
        'ticket_price',
        'open_hours',
        'close_hours',
        'cover_image',
        'phone',
        'status',
    ];

    protected $casts = [
        'maps_link' => 'string'
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
