<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plat;
use App\Models\Recommendation;
use App\Jobs\AnalyzeRecommendationJob;

class RecommendationController extends Controller
{

    public function analyze($plate_id, Request $request)
    {
        $user = $request->user();
        $plate = Plat::with('ingredients')->findOrFail($plate_id);

        $rec = Recommendation::updateOrCreate(
            [
                'user_id' => $user->id,
                'plate_id' => $plate->id
            ],
            [
                'status' => 'processing'
            ]
        );

        AnalyzeRecommendationJob::dispatch($user, $plate);

        return response()->json([
            'status' => 'processing',
            'recommendation_id' => $rec->id
        ]);
    }


    public function index(Request $request)
    {
        return Recommendation::where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    public function show($plate_id, Request $request)
    {
        $rec = Recommendation::where('user_id', $request->user()->id)
            ->where('plate_id', $plate_id)
            ->first();

        if (!$rec) {
            return response()->json([
                'message' => 'Recommendation not found'
            ], 404);
        }

        return response()->json($rec);
    }
}