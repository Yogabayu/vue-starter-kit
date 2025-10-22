<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function update($code)
    {
        try {
            $villages = Village::where('district_id', $code)->get();

            if ($villages->isNotEmpty()) {
                return response()->json([
                    'message' => 'Villages already up to date.',
                    'data' => $villages,
                ]);
            }

            $response = Http::get("https://wilayah.id/api/villages/{$code}.json");

            if (! $response->successful()) {
                throw new \Exception('Failed to fetch data from API');
            }

            // Simpan data baru
            $district = District::where('code', $code)->first();

            $villagesData = collect($response->json('data'))->map(fn($item) => [
                'code' => $item['code'],
                'name' => $item['name'],
                'district_id' => $district?->id
            ]);

            Village::upsert($villagesData->toArray(), ['code'], ['name', 'district_id']);

            Log::info("Villages for district {$code} updated successfully.");

            return response()->json([
                'message' => 'Villages updated successfully.',
                'data' => $villagesData,
            ]);
        } catch (\Throwable $th) {
            Log::error("Failed to update villages for {$code}: {$th->getMessage()}");

            return response()->json([
                'error' => 'Failed to update villages',
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
