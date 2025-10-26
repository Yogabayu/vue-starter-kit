<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventCategory;

class EventCategoryController extends Controller
{
    public function index()
    {
        return response()->json(['data' => EventCategory::orderBy('name')->get()]);
    }
}

