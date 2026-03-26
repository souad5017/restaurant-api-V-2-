<?php

namespace App\Services\AI;

class RecommendationPrompt
{
    public static function build($plateName, $ingredients, $restrictions)
    {
        return "
Analyze the nutritional compatibility between this dish and the user's dietary restrictions.

DISH: {$plateName}
INGREDIENT TAGS: " . implode(', ', $ingredients) . "
USER RESTRICTIONS: " . implode(', ', $restrictions) . "

Tag mapping rules:
- vegan conflicts with: contains_meat, contains_lactose
- no_sugar conflicts with: contains_sugar
- no_cholesterol conflicts with: contains_cholesterol
- gluten_free conflicts with: contains_gluten
- no_lactose conflicts with: contains_lactose

Calculate score: start at 100, subtract 25 for each conflict found.

Respond ONLY with this JSON:
{\"score\": <0-100>, \"warning_message\": \"<in French if score < 50, else empty string>\"}
";
    }
}