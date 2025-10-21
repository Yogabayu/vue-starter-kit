<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Village;
use Illuminate\Http\Request;

class VillageController extends Controller
{
    public function index()
    {
        try {
            $villages = Village::all();
            return response()->json($villages);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch villages'], 500);
        }
    }

    public function update()
    {
        try {
            $response = Http::get("https://wilayah.id/api/villages/{$code}.json");
            if ($response->successful()) {
                $villagesData = $response->json();
                foreach ($villagesData['data'] as $data) {
                    Village::updateOrCreate(
                        ['code' => $data['code']],
                        ['name' => $data['name']]
                    );
                }
            }
            Log::info('Villages updated successfully.');
            return response()->json(['message' => 'Villages updated successfully.']);
        } catch (\Throwable $th) {
            Log::error('Failed to update villages: ' . $th->getMessage());
        }
    }
}
