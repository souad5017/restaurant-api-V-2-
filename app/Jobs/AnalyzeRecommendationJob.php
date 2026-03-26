<?php

namespace App\Jobs;

use App\Models\Recommendation;
use App\Services\AI\RecommendationPrompt;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;

class AnalyzeRecommendationJob implements ShouldQueue
{
    use Queueable, Dispatchable,  InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $plate;

    /**
     * Create a new job instance.
     */

    public function __construct($user, $plate)
    {
        $this->user = $user;
        $this->plate = $plate;
    }

    /**
     * Execute the job.
     */

    public function handle()
    {
        $ingredients = [];

        foreach ($this->plate->ingredients as $ingredient) {
            $ingredients = array_merge($ingredients, $ingredient->tags ?? []);
        }

        $restrictions = $this->user->dietary_tags ?? [];

        $prompt = RecommendationPrompt::build(
            $this->plate->name,
            $ingredients,
            $restrictions
        );

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.groq.key'),
            'Content-Type' => 'application/json',
        ])->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => 'llama-3.1-8b-instant',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ]
        ]);

        $content = $response->json('choices.0.message.content') ?? '';

        $result = $this->parseResponse($content);

        Recommendation::updateOrCreate(
            [
                'user_id' => $this->user->id,
                'plate_id' => $this->plate->id
            ],
            [
                'score' => $result['score'],
                'label' => $result['label'],
                'warning_message' => $result['warning_message'],
                'status' => 'ready'
            ]
        );
    }
      private function parseResponse(string $text): array
    {
        $text = preg_replace('/```json|```/', '', $text);
        $text = trim($text);

        preg_match('/\{.*\}/s', $text, $matches);
        $data = json_decode($matches[0] ?? '{}', true);

        if (!isset($data['score'])) {
            return [
                'score' => 50,
                'label' => '🟡 Recommended with notes',
                'warning_message' => null,
            ];
        }

        $score = max(0, min(100, (int) $data['score']));
        $warning = $data['warning_message'] ?? '';

        $label = match (true) {
            $score >= 80 => '✅ Highly Recommended',
            $score >= 50 => '🟡 Recommended with notes',
            default => '⚠️ Not Recommended',
        };

        return [
            'score' => $score,
            'label' => $label,
            'warning_message' => $score < 50 ? $warning : null,
        ];
    }
}
