<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DistrictController extends Controller
{
    public function index()
    {
        try {
            $districts = District::all();
            return response()->json(['data' => $districts, 'message' => 'Districts fetched successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch districts'], 500);
        }
    }

    public function update($code)
    {
        try {
            $response = Http::get("https://wilayah.id/api/districts/{$code}.json");
            if ($response->successful()) {
                $districtsData = $response->json();
                foreach ($districtsData['data'] as $data) {
                    District::updateOrCreate(
                        ['code' => $data['code']],
                        ['name' => $data['name']]
                    );
                }
            }
            Log::info('Districts updated successfully.');
            return response()->json(['message' => 'Districts updated successfully.']);
        } catch (\Throwable $th) {
            Log::error('Failed to update districts: ' . $th->getMessage());
        }
    }
}
