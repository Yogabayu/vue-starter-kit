<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailDestination extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'detail_destinations';

    protected $fillable = [
        'destination_id',
        'description',
        'address',
        'district_id',
        'village_id',
        'map_url',
        'ticket_price',
        'currency',
        'phone',
        'status',
        'published_at',
    ];

    protected $casts = [
        'map_url' => 'string',
        'ticket_price' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }
}
