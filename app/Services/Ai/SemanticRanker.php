<?php

namespace App\Services\Ai;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Client for the Python semantic matching service.
 *
 * - Disabled unless an admin switches on "AI-assisted ranking" and AI_SERVICE_URL is set.
 * - Never throws: on timeout or error it returns null and the caller keeps keyword matching.
 *   After a failure it stops calling the service for `retry_after_seconds` (a simple circuit breaker),
 *   so a down service never slows pages down repeatedly.
 * - Only ranks: every hard rule (identity, strength, form, eligibility, availability) is applied in Laravel.
 */
class SemanticRanker
{
    private const DOWN_KEY = 'ai-service:down';

    public function enabled(): bool
    {
        return (bool) Setting::get('ai_matching') && filled(config('services.ai.url')) && ! Cache::has(self::DOWN_KEY);
    }

    /**
     * @param  array<int|string, string>  $candidates  id => medicine description
     * @return array<string, array{similarity: float, score: int}>|null keyed by candidate id, best first; null if unavailable
     */
    public function rank(string $query, array $candidates): ?array
    {
        if (! $this->enabled() || $candidates === [] || trim($query) === '') {
            return null;
        }

        $results = [];
        foreach (array_chunk($candidates, 200, true) as $chunk) {
            $payload = [
                'query' => mb_substr($query, 0, 500),
                'candidates' => collect($chunk)->map(fn ($text, $id) => ['id' => (string) $id, 'text' => mb_substr($text, 0, 500)])->values()->all(),
            ];
            try {
                $response = Http::baseUrl(rtrim(config('services.ai.url'), '/'))
                    ->withHeaders(['X-API-Key' => (string) config('services.ai.key')])
                    ->acceptJson()->timeout(config('services.ai.timeout'))->connectTimeout(2)
                    ->post('/v1/rank', $payload)->throw();
            } catch (Throwable $e) {
                $this->markDown($e->getMessage());

                return null;
            }
            foreach ($response->json('results', []) as $row) {
                $results[(string) $row['id']] = ['similarity' => (float) $row['similarity'], 'score' => (int) $row['score']];
            }
        }
        uasort($results, fn ($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $results;
    }

    /** Live status for the admin settings page. Always calls the service. */
    public function health(): array
    {
        if (blank(config('services.ai.url'))) {
            return ['ok' => false, 'message' => 'AI_SERVICE_URL is not configured.'];
        }
        try {
            $body = Http::baseUrl(rtrim(config('services.ai.url'), '/'))->acceptJson()->timeout(2)->get('/health')->throw()->json();
            Cache::forget(self::DOWN_KEY);

            return ['ok' => ($body['status'] ?? null) === 'ok', 'model' => $body['model'] ?? null, 'message' => ($body['status'] ?? 'unknown') === 'ok' ? 'Online' : 'Loading model'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Not reachable: '.str($e->getMessage())->limit(120)];
        }
    }

    private function markDown(string $reason): void
    {
        Cache::put(self::DOWN_KEY, true, now()->addSeconds(config('services.ai.retry_after_seconds')));
        Log::warning('AI service unavailable, falling back to keyword matching: '.$reason);
    }
}
