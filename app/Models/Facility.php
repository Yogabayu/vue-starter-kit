<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug', 'name', 'icon',
    ];

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class, 'destination_facility', 'facility_id', 'destination_id');
    }
}
