<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Plat;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Recommendation;

class AdminStatsController extends Controller
{
    public function index()
    {
        $totalPlates = Plat::count();
        $totalCategories = Category::count();
        $totalIngredients = Ingredient::count();

        $topPlate = Recommendation::select('plate_id')
            ->selectRaw('AVG(score) as avg_score')
            ->groupBy('plate_id')
            ->orderByDesc('avg_score')
            ->first();

        $topCategory = Plat::select('category_id')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->first();

        return response()->json([
            'total_plates' => $totalPlates,
            'total_categories' => $totalCategories,
            'total_ingredients' => $totalIngredients,
            'top_plate_id' => $topPlate?->plate_id,
            'top_plate_score' => $topPlate?->avg_score,
            'top_category_id' => $topCategory?->category_id,
            'top_category_count' => $topCategory?->count,
        ]);
    }
}