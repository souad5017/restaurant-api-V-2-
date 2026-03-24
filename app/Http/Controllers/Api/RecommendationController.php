<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plat;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function analyze($plate_id, Request $request)
    {
        $user = $request->user();

        $plate = Plat::with('ingredients')->findOrFail($plate_id);

        $score = 100;
        $warnings = [];

        $userTags = $user->dietary_tags ?? [];

        foreach ($plate->ingredients as $ingredient) {
            foreach ($ingredient->tags ?? [] as $tag) {

                if ($tag == 'contains_sugar' && in_array('no_sugar', $userTags)) {
                    $score -= 20;
                    $warnings[] = "Contains sugar";
                }

                if ($tag == 'contains_meat' && in_array('vegan', $userTags)) {
                    $score -= 30;
                    $warnings[] = "Contains meat";
                }

                if ($tag == 'contains_gluten' && in_array('gluten_free', $userTags)) {
                    $score -= 20;
                    $warnings[] = "Contains gluten";
                }

                if ($tag == 'contains_lactose' && in_array('no_lactose', $userTags)) {
                    $score -= 20;
                    $warnings[] = "Contains lactose";
                }
            }
        }

        $score = max($score, 0);

        // label
        if ($score >= 80) {
            $label = "Highly Recommended";
        } elseif ($score >= 50) {
            $label = "Recommended with notes";
        } else {
            $label = "Not Recommended";
        }

        $warningMessage = implode(', ', array_unique($warnings));

        return response()->json([
            'score' => $score,
            'label' => $label,
            'warning_message' => $warningMessage
        ]);
    }
}