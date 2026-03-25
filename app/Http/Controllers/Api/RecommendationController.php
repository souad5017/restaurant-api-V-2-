<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Plat;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    public function analyze($plate_id, Request $request)
    {
        $user = $request->user();

        $plate = Plat::with('ingredients')->findOrFail($plate_id);


        $ingredients = [];
        foreach ($plate->ingredients as $ingredient) {
            $ingredients = array_merge($ingredients, $ingredient->tags ?? []);
        }

        $restrictions = $user->dietary_tags ?? [];

        // PROMPT
        $prompt = "
Analyze the nutritional compatibility between this dish and the user's dietary restrictions.

DISH: {$plate->name}
INGREDIENT TAGS: " . implode(', ', $ingredients) . "
USER RESTRICTIONS: " . implode(', ', $restrictions) . "

Tag mapping rules:
- vegan conflicts with: contains_meat, contains_lactose
- no_sugar conflicts with: contains_sugar
- no_cholesterol conflicts with: contains_cholesterol
- gluten_free conflicts with: contains_gluten
- no_lactose conflicts with: contains_lactose

Calculate score: start at 100, subtract 25 for each conflict found.

Respond ONLY with this JSON (no markdown, no explanation):
{\"score\": <0-100>, \"warning_message\": \"<in French if score < 50, else empty string>\"}
";

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type' => 'application/json',
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
               'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ]);

            if ($response->failed()) {
                Log::error('Groq API Error', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return response()->json([
                    'error' => 'Groq API failed'
                ], 500);
            }

            $content = $response->json('choices.0.message.content') ?? '';

            // Parse response
            $result = $this->parseResponse($content);

            return response()->json($result);
        } catch (\Exception $e) {

            Log::error('OpenAI Error', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'AI service unavailable'
            ], 500);
        }
    }


    private function parseResponse(string $text): array
    {

        $text = preg_replace('/```json|```/', '', $text);
        $text = trim($text);

        preg_match('/\{.*\}/s', $text, $matches);
        $data = json_decode($matches[0] ?? '{}', true);

        if (!isset($data['score'])) {
            Log::warning('AI response parsing failed', ['text' => $text]);
            return [
                'score'           => 50,
                'label'           => '🟡 Recommended with notes',
                'warning_message' => null,
            ];
        }

        $score = max(0, min(100, (int) $data['score']));
        $warning = $data['warning_message'] ?? '';

        $label = match (true) {
            $score >= 80 => '✅ Highly Recommended',
            $score >= 50 => '🟡 Recommended with notes',
            default      => '⚠️ Not Recommended',
        };

        return [
            'score'           => $score,
            'label'           => $label,
            'warning_message' => $score < 50 ? $warning : null,
        ];
    }
}
