<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;

class FacilityController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Facility::orderBy('name')->get()]);
    }
}

